<?php declare(strict_types = 1);

/**
 * PairingController.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\HomeKit\Controllers;

use Brick\Math;
use Doctrine\DBAL;
use Elliptic\EdDSA;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Models;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http as SlimRouterHttp;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use RuntimeException;
use SodiumException;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_pop;
use function array_values;
use function assert;
use function bin2hex;
use function Curve25519\publicKey;
use function Curve25519\sharedKey;
use function hash_hkdf;
use function hex2bin;
use function is_array;
use function is_int;
use function is_string;
use function pack;
use function sodium_crypto_aead_chacha20poly1305_ietf_decrypt;
use function sodium_crypto_aead_chacha20poly1305_ietf_encrypt;
use function strval;
use function unpack;

/**
 * Connector pairing process controller
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PairingController extends BaseController
{

	private const MAX_AUTHENTICATION_ATTEMPTS = 100;

	private const SRP_USERNAME = 'Pair-Setup';

	private const SALT_ENCRYPT = 'Pair-Setup-Encrypt-Salt';

	private const INFO_ENCRYPT = 'Pair-Setup-Encrypt-Info';

	private const SALT_CONTROLLER = 'Pair-Setup-Controller-Sign-Salt';

	private const INFO_CONTROLLER = 'Pair-Setup-Controller-Sign-Info';

	private const SALT_ACCESSORY = 'Pair-Setup-Accessory-Sign-Salt';

	private const INFO_ACCESSORY = 'Pair-Setup-Accessory-Sign-Info';

	private const SALT_VERIFY = 'Pair-Verify-Encrypt-Salt';

	private const INFO_VERIFY = 'Pair-Verify-Encrypt-Info';

	private const NONCE_SETUP_M5
		= [
			0, // 0
			0, // 0
			0, // 0
			0, // 0
			80, // P
			83, // S
			45, // -
			77, // M
			115, // s
			103, // g
			48, // 0
			53, // 5
		];

	private const NONCE_SETUP_M6
		= [
			0, // 0
			0, // 0
			0, // 0
			0, // 0
			80, // P
			83, // S
			45, // -
			77, // M
			115, // s
			103, // g
			48, // 0
			54, // 6
		];

	private const NONCE_VERIFY_M2
		= [
			0, // 0
			0, // 0
			0, // 0
			0, // 0
			80, // P
			86, // V
			45, // -
			77, // M
			115, // s
			103, // g
			48, // 0
			50, // 2
		];

	private const NONCE_VERIFY_M3
		= [
			0, // 0
			0, // 0
			0, // 0
			0, // 0
			80, // P
			86, // V
			45, // -
			77, // M
			115, // s
			103, // g
			48, // 0
			51, // 3
		];

	private bool $activePairing = false;

	private int $pairingAttempts = 0;

	private Protocol\Srp|null $srp = null;

	private HomeKitTypes\TlvState $expectedState;

	private EdDSA $edDsa;

	public function __construct(
		private readonly Protocol\Tlv $tlv,
		private readonly Models\Entities\Clients\ClientsRepository $clientsRepository,
		private readonly Models\Entities\Clients\ClientsManager $clientsManager,
		private readonly HomeKitHelpers\Connector $connectorHelper,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $propertiesManagers,
		private readonly PersistenceHelpers\Database $databaseHelper,
	)
	{
		$this->edDsa = new EdDSA('ed25519');

		$this->expectedState = HomeKitTypes\TlvState::M1;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setup(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->logger->debug(
			'Requested pairing setup',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'query' => $request->getQueryParams(),
				],
			],
		);

		$connectorId = strval($request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR));

		if (!Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\InvalidState('Connector id could not be determined');
		}

		$connectorId = Uuid\Uuid::fromString($connectorId);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($connectorId);

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			throw new Exceptions\InvalidState('Connector could not be loaded');
		}

		if ($this->connectorHelper->isPaired($connector)) {
			$result = [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		} else {
			$tlv = $this->tlv->decode($request->getBody()->getContents());

			if ($tlv === []) {
				throw new Exceptions\InvalidArgument('Provided TLV content is not valid');
			}

			$tlvEntry = array_pop($tlv);

			$requestedState = array_key_exists(HomeKitTypes\TlvCode::STATE->value, $tlvEntry)
				? $tlvEntry[HomeKitTypes\TlvCode::STATE->value]
				: null;

			if (
				$requestedState === HomeKitTypes\TlvState::M1->value
				&& array_key_exists(HomeKitTypes\TlvCode::METHOD->value, $tlvEntry)
				&& $tlvEntry[HomeKitTypes\TlvCode::METHOD->value] === HomeKitTypes\TlvMethod::RESERVED->value
			) {
				$result = $this->srpStart($connector, $request);

				$this->expectedState = HomeKitTypes\TlvState::M3;

			} elseif (
				$requestedState === HomeKitTypes\TlvState::M3->value
				&& array_key_exists(HomeKitTypes\TlvCode::PUBLIC_KEY->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value])
				&& array_key_exists(HomeKitTypes\TlvCode::PROOF->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::PROOF->value])
			) {
				$result = $this->srpFinish(
					$connector,
					$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value],
					$tlvEntry[HomeKitTypes\TlvCode::PROOF->value],
					$request,
				);

				$this->expectedState = HomeKitTypes\TlvState::M5;

			} elseif (
				$requestedState === HomeKitTypes\TlvState::M5->value
				&& array_key_exists(HomeKitTypes\TlvCode::ENCRYPTED_DATA->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::ENCRYPTED_DATA->value])
			) {
				$result = $this->exchange(
					$connector,
					$tlvEntry[HomeKitTypes\TlvCode::ENCRYPTED_DATA->value],
					$request,
				);

				$this->expectedState = HomeKitTypes\TlvState::M1;

			} else {
				throw new Exceptions\InvalidState('Unknown data received');
			}
		}

		if (array_key_exists(HomeKitTypes\TlvCode::ERROR->value, $result)) {
			$this->srp = null;
			$this->activePairing = false;
			$this->expectedState = HomeKitTypes\TlvState::M1;
		}

		$response = $response->withStatus(StatusCodeInterface::STATUS_OK);
		$response = $response->withHeader('Content-Type', Servers\Http::PAIRING_CONTENT_TYPE);
		$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString($this->tlv->encode($result)));

		return $response;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function verify(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->logger->debug(
			'Requested pairing verify',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'query' => $request->getQueryParams(),
				],
			],
		);

		$connectorId = strval($request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR));

		if (!Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\InvalidState('Connector id could not be determined');
		}

		$connectorId = Uuid\Uuid::fromString($connectorId);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($connectorId);

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			throw new Exceptions\InvalidState('Connector could not be loaded');
		}

		if (!$this->connectorHelper->isPaired($connector)) {
			$result = [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		} else {
			$tlv = $this->tlv->decode($request->getBody()->getContents());

			if ($tlv === []) {
				throw new Exceptions\InvalidArgument('Provided TLV content is not valid');
			}

			$tlvEntry = array_pop($tlv);

			$requestedState = array_key_exists(HomeKitTypes\TlvCode::STATE->value, $tlvEntry)
				? $tlvEntry[HomeKitTypes\TlvCode::STATE->value]
				: null;

			if (
				$requestedState === HomeKitTypes\TlvState::M1->value
				&& array_key_exists(HomeKitTypes\TlvCode::PUBLIC_KEY->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value])
			) {
				$result = $this->verifyStart($connector, $tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value], $request);

			} elseif (
				$requestedState === HomeKitTypes\TlvState::M3->value
				&& array_key_exists(HomeKitTypes\TlvCode::ENCRYPTED_DATA->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::ENCRYPTED_DATA->value])
			) {
				$result = $this->verifyFinish(
					$connector,
					$tlvEntry[HomeKitTypes\TlvCode::ENCRYPTED_DATA->value],
					$request,
				);

			} else {
				throw new Exceptions\InvalidState('Unknown data received');
			}
		}

		$response = $response->withStatus(StatusCodeInterface::STATUS_OK);
		$response = $response->withHeader('Content-Type', Servers\Http::PAIRING_CONTENT_TYPE);
		$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString($this->tlv->encode($result)));

		return $response;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function pairings(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$this->logger->debug(
			'Requested clients pairing update',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'query' => $request->getQueryParams(),
				],
			],
		);

		$connectorId = strval($request->getAttribute(Servers\Http::REQUEST_ATTRIBUTE_CONNECTOR));

		if (!Uuid\Uuid::isValid($connectorId)) {
			throw new Exceptions\InvalidState('Connector id could not be determined');
		}

		$connectorId = Uuid\Uuid::fromString($connectorId);

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($connectorId);

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			throw new Exceptions\InvalidState('Connector could not be loaded');
		}

		if (!$this->connectorHelper->isPaired($connector)) {
			$result = [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		} else {
			$tlv = $this->tlv->decode($request->getBody()->getContents());

			if ($tlv === []) {
				throw new Exceptions\InvalidArgument('Provided TLV content is not valid');
			}

			$tlvEntry = array_pop($tlv);

			$requestedState = array_key_exists(HomeKitTypes\TlvCode::STATE->value, $tlvEntry)
				? $tlvEntry[HomeKitTypes\TlvCode::STATE->value]
				: null;
			$method = array_key_exists(HomeKitTypes\TlvCode::METHOD->value, $tlvEntry)
				? $tlvEntry[HomeKitTypes\TlvCode::METHOD->value]
				: null;

			if (
				$method === HomeKitTypes\TlvMethod::LIST_PAIRINGS->value
				&& $requestedState === HomeKitTypes\TlvState::M1->value
			) {
				$result = $this->listPairings($connector, $request);

			} elseif (
				$method === HomeKitTypes\TlvMethod::ADD_PAIRING->value
				&& $requestedState === HomeKitTypes\TlvState::M1->value
				&& array_key_exists(HomeKitTypes\TlvCode::IDENTIFIER->value, $tlvEntry)
				&& is_string($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value])
				&& array_key_exists(HomeKitTypes\TlvCode::PUBLIC_KEY->value, $tlvEntry)
				&& is_array($tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value])
				&& array_key_exists(HomeKitTypes\TlvCode::PERMISSIONS->value, $tlvEntry)
				&& is_int($tlvEntry[HomeKitTypes\TlvCode::PERMISSIONS->value])
			) {
				$result = $this->addPairing(
					$connector,
					$tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value],
					$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value],
					$tlvEntry[HomeKitTypes\TlvCode::PERMISSIONS->value],
					$request,
				);
			} elseif (
				$method === HomeKitTypes\TlvMethod::REMOVE_PAIRING->value
				&& $requestedState === HomeKitTypes\TlvState::M1->value
				&& array_key_exists(HomeKitTypes\TlvCode::IDENTIFIER->value, $tlvEntry)
				&& is_string($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value])
			) {
				$result = $this->removePairing(
					$connector,
					$tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value],
					$request,
				);
			} else {
				throw new Exceptions\InvalidState('Unknown data received');
			}
		}

		$response = $response->withStatus(StatusCodeInterface::STATUS_OK);
		$response = $response->withHeader('Content-Type', Servers\Http::PAIRING_CONTENT_TYPE);
		$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString($this->tlv->encode($result)));

		return $response;
	}

	/**
	 * @return array<int, array<int, (int|array<int>|string)>>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws Math\Exception\MathException
	 * @throws Math\Exception\NegativeNumberException
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function srpStart(
		Documents\Connectors\Connector $connector,
		Message\ServerRequestInterface $request,
	): array
	{
		if ($this->connectorHelper->isPaired($connector)) {
			$this->logger->error(
				'Accessory already paired, cannot accept additional pairings',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNAVAILABLE->value,
				],
			];
		}

		if ($this->pairingAttempts >= self::MAX_AUTHENTICATION_ATTEMPTS) {
			$this->logger->error(
				'Max authentication attempts reached',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::MAX_TRIES->value,
				],
			];
		}

		if ($this->expectedState !== HomeKitTypes\TlvState::M1) {
			$this->logger->error(
				'Unexpected pairing setup state. Expected is M1',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		if ($this->activePairing) {
			$this->logger->error(
				'Currently perform pair setup operation with a different controller',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::BUSY->value,
				],
			];
		}

		$this->activePairing = true;

		$this->srp = new Protocol\Srp(self::SRP_USERNAME, $this->connectorHelper->getPinCode($connector));

		$serverPublicKey = unpack('C*', $this->srp->getServerPublicKey()->toBytes(false));

		if ($serverPublicKey === false) {
			$this->logger->error(
				'Server public key could not be converted to bytes',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$salt = unpack('C*', $this->srp->getSalt());

		if ($salt === false) {
			$this->logger->error(
				'Slat could not be converted to bytes',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$this->logger->debug(
			'SRP start success',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'pairing' => [
					'type' => 'srp-start',
					'state' => HomeKitTypes\TlvState::M2->value,
				],
			],
		);

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
				HomeKitTypes\TlvCode::PUBLIC_KEY->value => $serverPublicKey,
				HomeKitTypes\TlvCode::SALT->value => $salt,
			],
		];
	}

	/**
	 * @param array<int> $clientPublicKey
	 * @param array<int> $clientProof
	 *
	 * @return array<int, array<int, (int|array<int>|string)>>
	 *
	 * @throws Math\Exception\MathException
	 * @throws Math\Exception\NumberFormatException
	 */
	private function srpFinish(
		Documents\Connectors\Connector $connector,
		array $clientPublicKey,
		array $clientProof,
		Message\ServerRequestInterface $request,
	): array
	{
		if ($this->srp === null || $this->expectedState !== HomeKitTypes\TlvState::M3) {
			$this->logger->error(
				'Unexpected pairing setup state. Expected is M3',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$this->srp->computeSharedSessionKey(
			Math\BigInteger::fromBytes(pack('C*', ...$clientPublicKey), false),
		);

		if (!$this->srp->verifyProof(pack('C*', ...$clientProof))) {
			$this->logger->error(
				'Incorrect pin code, try again',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		if ($this->srp->getServerProof() === null) {
			$this->logger->error(
				'Server proof of session key is not computed',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$serverProof = unpack('C*', $this->srp->getServerProof());

		if ($serverProof === false) {
			$this->logger->error(
				'Server proof of session key could not be converted to binary array',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'srp-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$this->logger->debug(
			'SRP finish success',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'pairing' => [
					'type' => 'srp-finish',
					'state' => HomeKitTypes\TlvState::M4->value,
				],
			],
		);

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
				HomeKitTypes\TlvCode::PROOF->value => $serverProof,
			],
		];
	}

	/**
	 * @param array<int> $encryptedData
	 *
	 * @return array<int, array<int, (int|array<int>|string)>>
	 *
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function exchange(
		Documents\Connectors\Connector $connector,
		array $encryptedData,
		Message\ServerRequestInterface $request,
	): array
	{
		if ($this->srp === null || $this->expectedState !== HomeKitTypes\TlvState::M5) {
			$this->logger->error(
				'Unexpected pairing setup state. Expected is M5',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$decryptKey = hash_hkdf(
			'sha512',
			(string) $this->srp->getSessionKey(),
			32,
			self::INFO_ENCRYPT,
			self::SALT_ENCRYPT,
		);

		try {
			$decryptedData = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
				pack('C*', ...$encryptedData),
				'',
				pack('C*', ...self::NONCE_SETUP_M5),
				$decryptKey,
			);
		} catch (SodiumException $ex) {
			$this->logger->error(
				'Data could not be encrypted',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		if ($decryptedData === false) {
			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		try {
			$tlv = $this->tlv->decode($decryptedData);
		} catch (Exceptions\InvalidArgument) {
			$this->logger->error(
				'Unable to decode decrypted tlv data',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		if ($tlv === []) {
			$this->logger->error(
				'Data in decoded decrypted tlv data are missing',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$tlvEntry = array_pop($tlv);

		if (
			!array_key_exists(HomeKitTypes\TlvCode::IDENTIFIER->value, $tlvEntry)
			|| !is_string($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value])
			|| !array_key_exists(HomeKitTypes\TlvCode::PUBLIC_KEY->value, $tlvEntry)
			|| !is_array($tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value])
			|| !array_key_exists(HomeKitTypes\TlvCode::SIGNATURE->value, $tlvEntry)
			|| !is_array($tlvEntry[HomeKitTypes\TlvCode::SIGNATURE->value])
		) {
			$this->logger->error(
				'Data in decoded decrypted tlv data are invalid',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$iosDeviceX = hash_hkdf(
			'sha512',
			(string) $this->srp->getSessionKey(),
			32,
			self::INFO_CONTROLLER,
			self::SALT_CONTROLLER,
		);

		$iosDeviceInfo = $iosDeviceX
			. $tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value]
			. pack('C*', ...$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value]);

		if (
			!$this->edDsa->verify(
				unpack('C*', $iosDeviceInfo),
				$tlvEntry[HomeKitTypes\TlvCode::SIGNATURE->value],
				$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value],
			)
		) {
			$this->logger->error(
				'iOS device info ed25519 signature verification is failed',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		$this->databaseHelper->transaction(
			function () use ($tlvEntry, $connector): void {
				$connector = $this->connectorsRepository->find(
					$connector->getId(),
					Entities\Connectors\Connector::class,
				);
				assert($connector instanceof Entities\Connectors\Connector);

				$findClientQuery = new Queries\Entities\FindClients();
				$findClientQuery->forConnector($connector);
				$findClientQuery->byUid($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value]);

				$client = $this->clientsRepository->findOneBy($findClientQuery);

				if ($client !== null) {
					$this->clientsManager->update($client, Utils\ArrayHash::from([
						'publicKey' => pack('C*', ...$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value]),
					]));
				} else {
					$this->clientsManager->create(Utils\ArrayHash::from([
						'uid' => $tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value],
						'publicKey' => pack('C*', ...$tlvEntry[HomeKitTypes\TlvCode::PUBLIC_KEY->value]),
						'connector' => $connector,
					]));
				}
			},
		);

		// M6 response generation

		$serverX = hash_hkdf(
			'sha512',
			(string) $this->srp->getSessionKey(),
			32,
			self::INFO_ACCESSORY,
			self::SALT_ACCESSORY,
		);

		$serverSecret = $this->connectorHelper->getServerSecret($connector);

		if ($serverSecret === null) {
			$this->logger->error(
				'Server secret key is not configured',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$serverSecret = hex2bin($serverSecret);

		$serverPrivateKey = $this->edDsa->keyFromSecret(
			unpack('C*', (string) $serverSecret),
		);
		$serverPublicKey = $serverPrivateKey->getPublic();

		$serverInfo = $serverX . $this->connectorHelper->getMacAddress($connector) . pack('C*', ...$serverPublicKey);
		$serverSignature = $serverPrivateKey->sign(unpack('C*', $serverInfo))->toBytes();

		$responseInnerData = [
			[
				HomeKitTypes\TlvCode::IDENTIFIER->value => $this->connectorHelper->getMacAddress($connector),
				HomeKitTypes\TlvCode::PUBLIC_KEY->value => $serverPublicKey,
				HomeKitTypes\TlvCode::SIGNATURE->value => $serverSignature,
			],
		];

		try {
			$responseEncryptedData = unpack('C*', sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
				$this->tlv->encode($responseInnerData),
				'',
				pack('C*', ...self::NONCE_SETUP_M6),
				$decryptKey,
			));
		} catch (SodiumException $ex) {
			$this->logger->error(
				'Data could not be encrypted',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		if ($responseEncryptedData === false) {
			$this->logger->error(
				'Encrypted data could not be converted to bytes',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'exchange',
						'state' => HomeKitTypes\TlvState::M6->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$this->activePairing = false;

		$this->logger->debug(
			'Pair finish exchange success',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'pairing' => [
					'type' => 'exchange',
					'state' => HomeKitTypes\TlvState::M6->value,
				],
			],
		);

		$this->setConfiguration(
			$connector,
			HomeKitTypes\ConnectorPropertyIdentifier::PAIRED,
			true,
		);

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M6->value,
				HomeKitTypes\TlvCode::ENCRYPTED_DATA->value => $responseEncryptedData,
			],
		];
	}

	/**
	 * @param array<int> $clientPublicKey
	 *
	 * @return array<int, array<int, (int|array<int>|string)>>
	 *
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function verifyStart(
		Documents\Connectors\Connector $connector,
		array $clientPublicKey,
		Message\ServerRequestInterface $request,
	): array
	{
		$serverSecret = $this->connectorHelper->getServerSecret($connector);

		if ($serverSecret === null) {
			$this->logger->error(
				'Server secret key is not configured',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$serverSecret = hex2bin($serverSecret);

		$serverPublicKey = publicKey($serverSecret);
		$sharedSecret = sharedKey($serverSecret, pack('C*', ...$clientPublicKey));

		$serverInfo = $serverPublicKey . $this->connectorHelper->getMacAddress($connector) . pack(
			'C*',
			...$clientPublicKey,
		);

		$serverPrivateKey = $this->edDsa->keyFromSecret(
			unpack('C*', (string) $serverSecret),
		);
		$serverSignature = $serverPrivateKey->sign(unpack('C*', $serverInfo))->toBytes();

		$responseInnerData = [
			[
				HomeKitTypes\TlvCode::IDENTIFIER->value => $this->connectorHelper->getMacAddress($connector),
				HomeKitTypes\TlvCode::SIGNATURE->value => $serverSignature,
			],
		];

		$encodeKey = hash_hkdf(
			'sha512',
			$sharedSecret,
			32,
			self::INFO_VERIFY,
			self::SALT_VERIFY,
		);

		try {
			$responseEncryptedData = unpack('C*', sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
				$this->tlv->encode($responseInnerData),
				'',
				pack('C*', ...self::NONCE_VERIFY_M2),
				$encodeKey,
			));
		} catch (SodiumException $ex) {
			$this->logger->error(
				'Data could not be encrypted',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		if ($responseEncryptedData === false) {
			$this->logger->error(
				'Encrypted data could not be converted to bytes',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$serverPublicKey = unpack('C*', $serverPublicKey);

		if ($serverPublicKey === false) {
			$this->logger->error(
				'Accessory public key could not be converted to bytes',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-start',
						'state' => HomeKitTypes\TlvState::M2->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$this->setConfiguration(
			$connector,
			HomeKitTypes\ConnectorPropertyIdentifier::CLIENT_PUBLIC_KEY,
			bin2hex(pack('C*', ...$clientPublicKey)),
		);

		$this->setConfiguration(
			$connector,
			HomeKitTypes\ConnectorPropertyIdentifier::SHARED_KEY,
			bin2hex($sharedSecret),
		);

		$this->setConfiguration(
			$connector,
			HomeKitTypes\ConnectorPropertyIdentifier::HASHING_KEY,
			bin2hex($encodeKey),
		);

		$this->logger->debug(
			'Verify start success',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'pairing' => [
					'type' => 'verify-start',
					'state' => HomeKitTypes\TlvState::M2->value,
				],
			],
		);

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
				HomeKitTypes\TlvCode::PUBLIC_KEY->value => $serverPublicKey,
				HomeKitTypes\TlvCode::ENCRYPTED_DATA->value => $responseEncryptedData,
			],
		];
	}

	/**
	 * @param array<int> $encryptedData
	 *
	 * @return array<int, array<int, (int|array<int>|string)>>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function verifyFinish(
		Documents\Connectors\Connector $connector,
		array $encryptedData,
		Message\ServerRequestInterface $request,
	): array
	{
		try {
			$decryptedData = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
				pack('C*', ...$encryptedData),
				'',
				pack('C*', ...self::NONCE_VERIFY_M3),
				(string) hex2bin(strval($this->connectorHelper->getHashingKey($connector))),
			);
		} catch (SodiumException $ex) {
			$this->logger->error(
				'Data could not be encrypted',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		if ($decryptedData === false) {
			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		try {
			$tlv = $this->tlv->decode($decryptedData);
		} catch (Exceptions\InvalidArgument) {
			$this->logger->error(
				'Unable to decode decrypted tlv data',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		if ($tlv === []) {
			$this->logger->error(
				'Data in decoded decrypted tlv data are missing',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		$tlvEntry = array_pop($tlv);

		if (
			!array_key_exists(HomeKitTypes\TlvCode::IDENTIFIER->value, $tlvEntry)
			|| !is_string($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value])
			|| !array_key_exists(HomeKitTypes\TlvCode::SIGNATURE->value, $tlvEntry)
			|| !is_array($tlvEntry[HomeKitTypes\TlvCode::SIGNATURE->value])
		) {
			$this->logger->error(
				'Data in decoded decrypted tlv data are invalid',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		$findClientQuery = new Queries\Entities\FindClients();
		$findClientQuery->byConnectorId($connector->getId());
		$findClientQuery->byUid($tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value]);

		$client = $this->clientsRepository->findOneBy($findClientQuery);

		if ($client === null) {
			$this->logger->debug(
				'Pairing client instance is not created',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'client' => [
						'uid' => $tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value],
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		$serverSecret = $this->connectorHelper->getServerSecret($connector);

		if ($serverSecret === null) {
			$this->logger->error(
				'Server secret key is not configured',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		$serverSecret = hex2bin($serverSecret);

		$iosDeviceInfo
			= hex2bin(strval($this->connectorHelper->getClientPublicKey($connector)))
			. $tlvEntry[HomeKitTypes\TlvCode::IDENTIFIER->value]
			. publicKey($serverSecret);

		if (
			!$this->edDsa->verify(
				array_values((array) unpack('C*', $iosDeviceInfo)),
				$tlvEntry[HomeKitTypes\TlvCode::SIGNATURE->value],
				array_values((array) unpack('C*', $client->getPublicKey())),
			)
		) {
			$this->logger->error(
				'iOS device info ed25519 signature verification is failed',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'pairing-controller',
					'request' => [
						'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'pairing' => [
						'type' => 'verify-finish',
						'state' => HomeKitTypes\TlvState::M4->value,
					],
				],
			);

			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::AUTHENTICATION->value,
				],
			];
		}

		$this->logger->debug(
			'Verify finish success',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'pairing' => [
					'type' => 'verify-start',
					'state' => HomeKitTypes\TlvState::M4->value,
				],
			],
		);

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M4->value,
			],
		];
	}

	/**
	 * @return array<int, array<int, (int|array<int>|string)>>
	 */
	private function listPairings(
		Documents\Connectors\Connector $connector,
		Message\ServerRequestInterface $request,
	): array
	{
		$this->logger->debug(
			'Requested list pairings',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
			],
		);

		$result = [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
			],
		];

		try {
			$findClientsQuery = new Queries\Entities\FindClients();
			$findClientsQuery->byConnectorId($connector->getId());

			$clients = $this->clientsRepository->getResultSet($findClientsQuery);
		} catch (Throwable) {
			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		foreach ($clients as $client) {
			$result[] = [
				HomeKitTypes\TlvCode::IDENTIFIER->value => $client->getUid(),
				HomeKitTypes\TlvCode::PUBLIC_KEY->value => (array) unpack('C*', $client->getPublicKey()),
				HomeKitTypes\TlvCode::PERMISSIONS->value => $client->isAdmin()
					? HomeKitTypes\ClientPermission::ADMIN->value
					: HomeKitTypes\ClientPermission::USER->value,
			];
		}

		return $result;
	}

	/**
	 * @param array<int> $clientPublicKey
	 *
	 * @return array<int, array<int, int>>
	 */
	private function addPairing(
		Documents\Connectors\Connector $connector,
		string $clientUid,
		array $clientPublicKey,
		int $clientPermission,
		Message\ServerRequestInterface $request,
	): array
	{
		$this->logger->debug(
			'Requested add new pairing',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
			],
		);

		try {
			$findClientQuery = new Queries\Entities\FindClients();
			$findClientQuery->byUid($clientUid);
			$findClientQuery->byConnectorId($connector->getId());

			$client = $this->clientsRepository->findOneBy($findClientQuery);
		} catch (Throwable) {
			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		if ($client !== null) {
			if ($client->getPublicKey() !== pack('C*', ...$clientPublicKey)) {
				$this->logger->error(
					'Received iOS device public key does not match with previously saved key',
					[
						'source' => Sources\Connector::HOMEKIT->value,
						'type' => 'pairing-controller',
						'request' => [
							'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
						],
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'pairing' => [
							'type' => 'add-pairing',
							'state' => HomeKitTypes\TlvState::M2->value,
						],
					],
				);

				return [
					[
						HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
						HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
					],
				];
			} else {
				try {
					$this->databaseHelper->transaction(
						function () use ($client, $clientPermission): void {
							$this->clientsManager->update($client, Utils\ArrayHash::from([
								'admin' => $clientPermission === HomeKitTypes\ClientPermission::ADMIN->value,
							]));
						},
					);
				} catch (Throwable) {
					return [
						[
							HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
							HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
						],
					];
				}
			}
		} else {
			try {
				$this->databaseHelper->transaction(
					function () use ($connector, $clientUid, $clientPublicKey, $clientPermission): void {
						$connector = $this->connectorsRepository->find(
							$connector->getId(),
							Entities\Connectors\Connector::class,
						);
						assert($connector instanceof Entities\Connectors\Connector);

						$this->clientsManager->create(Utils\ArrayHash::from([
							'uid' => $clientUid,
							'publicKey' => pack('C*', ...$clientPublicKey),
							'admin' => $clientPermission === HomeKitTypes\ClientPermission::ADMIN->value,
							'connector' => $connector,
						]));
					},
				);
			} catch (Throwable) {
				return [
					[
						HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
						HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
					],
				];
			}
		}

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
			],
		];
	}

	/**
	 * @return array<int, array<int, int>>
	 */
	private function removePairing(
		Documents\Connectors\Connector $connector,
		string $clientUid,
		Message\ServerRequestInterface $request,
	): array
	{
		$this->logger->debug(
			'Requested remove pairing',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'pairing-controller',
				'request' => [
					'client_address' => strval($request->getServerParams()['REMOTE_ADDR']),
				],
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
			],
		);

		try {
			$this->databaseHelper->transaction(function () use ($connector, $clientUid): void {
				$findClientQuery = new Queries\Entities\FindClients();
				$findClientQuery->byUid($clientUid);
				$findClientQuery->byConnectorId($connector->getId());

				$client = $this->clientsRepository->findOneBy($findClientQuery);

				if ($client !== null) {
					$this->clientsManager->delete($client);
				}

				$findClientsQuery = new Queries\Entities\FindClients();
				$findClientsQuery->byConnectorId($connector->getId());

				$clients = $this->clientsRepository->getResultSet($findClientsQuery);

				if ($clients->count() === 0) {
					$this->setConfiguration(
						$connector,
						HomeKitTypes\ConnectorPropertyIdentifier::PAIRED,
						false,
					);

					$this->setConfiguration(
						$connector,
						HomeKitTypes\ConnectorPropertyIdentifier::CLIENT_PUBLIC_KEY,
					);

					$this->setConfiguration(
						$connector,
						HomeKitTypes\ConnectorPropertyIdentifier::SHARED_KEY,
					);

					$this->setConfiguration(
						$connector,
						HomeKitTypes\ConnectorPropertyIdentifier::HASHING_KEY,
					);

					$this->setConfiguration(
						$connector,
						HomeKitTypes\ConnectorPropertyIdentifier::CONFIG_VERSION,
						1,
					);
				}
			});
		} catch (Throwable) {
			return [
				[
					HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
					HomeKitTypes\TlvCode::ERROR->value => HomeKitTypes\TlvError::UNKNOWN->value,
				],
			];
		}

		return [
			[
				HomeKitTypes\TlvCode::STATE->value => HomeKitTypes\TlvState::M2->value,
			],
		];
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 */
	private function setConfiguration(
		Documents\Connectors\Connector $connector,
		HomeKitTypes\ConnectorPropertyIdentifier $type,
		string|int|float|bool|null $value = null,
	): void
	{
		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->byConnectorId($connector->getId());
		$findConnectorPropertyQuery->byIdentifier($type);

		$property = $this->propertiesRepository->findOneBy(
			$findConnectorPropertyQuery,
			DevicesEntities\Connectors\Properties\Variable::class,
		);
		assert(
			$property instanceof DevicesEntities\Connectors\Properties\Variable || $property === null,
		);

		if ($property === null) {
			if (
				$type === HomeKitTypes\ConnectorPropertyIdentifier::CLIENT_PUBLIC_KEY
				|| $type === HomeKitTypes\ConnectorPropertyIdentifier::SHARED_KEY
				|| $type === HomeKitTypes\ConnectorPropertyIdentifier::HASHING_KEY
			) {
				$this->databaseHelper->transaction(
					function () use ($connector, $type, $value): void {
						$connector = $this->connectorsRepository->find(
							$connector->getId(),
							Entities\Connectors\Connector::class,
						);
						assert($connector instanceof Entities\Connectors\Connector);

						$this->propertiesManagers->create(
							Utils\ArrayHash::from([
								'entity' => DevicesEntities\Connectors\Properties\Variable::class,
								'identifier' => $type->value,
								'dataType' => ValuesTypes\DataType::STRING,
								'value' => $value,
								'connector' => $connector,
							]),
						);
					},
				);
			} else {
				throw new Exceptions\InvalidState('Connector property could not be configured');
			}
		} else {
			$this->databaseHelper->transaction(
				function () use ($property, $value): void {
					$this->propertiesManagers->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $value,
						]),
					);
				},
			);
		}
	}

}
