<?php declare(strict_types = 1);

/**
 * Coap.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           14.07.22
 */

namespace FastyBird\Connector\Shelly\API;

use BadMethodCallException;
use Closure;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ToolsExceptions;
use FastyBird\Core\Schemas\Tools as ToolsSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Nette\Utils;
use React\Datagram;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function count;
use function explode;
use function is_array;
use function mb_convert_encoding;
use function md5;
use function pack;
use function preg_replace;
use function sprintf;
use function str_replace;
use function unpack;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 1 device CoAP interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1Coap
{

	use Nette\SmartObject;

	private const STATE_MESSAGE_CODE = 30;

	private const STATE_MESSAGE_SCHEMA_FILENAME = 'gen1_coap_state.json';

	/** @var array<Closure(Messages\Message $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(): void> */
	public array $onClosed = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	/** @var array<string, string> */
	private array $validationSchemas = [];

	/** @var array<string, string> */
	private array $lastMessage = [];

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Services\MulticastFactory $multicastFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Shelly\Logger $logger,
		private readonly ToolsSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$this->server = $this->multicastFactory->create(
			Shelly\Constants::COAP_ADDRESS,
			Shelly\Constants::COAP_PORT,
		);

		$this->server->on('message', function ($message, $remote): void {
			$this->handlePacket($message, $remote);
		});

		$this->server?->on('error', function (Throwable $ex): void {
			Utils\Arrays::invoke($this->onError, $ex);
		});

		$this->server?->on('close', function (): void {
			$this->logger->debug(
				'CoAP connection was successfully closed',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY->value,
					'type' => 'gen1-coap-api',
				],
			);

			Utils\Arrays::invoke($this->onClosed);
		});
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	private function handlePacket(string $packet, string $remote): void
	{
		$buffer = unpack('C*', $packet);

		if (!is_array($buffer) || count($buffer) < 10) {
			return;
		}

		$pos = 1;

		// Receive messages with ip from proxy
		if ($buffer[1] === 112 && $buffer[2] === 114 && $buffer[3] === 120 && $buffer[4] === 121) {
			$pos = 9;
		}

		$byte = $buffer[$pos];

		$tkl = $byte & 0x0F;

		$code = $buffer[$pos + 1];
		// $messageId = 256 * $buf[3] + $buf[4];

		$pos = $pos + 4 + $tkl;

		if ($code === self::STATE_MESSAGE_CODE) {
			$byte = $buffer[$pos];
			$totDelta = 0;

			$deviceType = null;
			$deviceIdentifier = null;

			while ($byte !== 0xFF) {
				$delta = $byte >> 4;
				$length = $byte & 0x0F;

				if ($delta === 13) {
					$pos++;
					$delta = $buffer[$pos] + 13;

				} elseif ($delta === 14) {
					$pos += 2;
					$delta = $buffer[$pos - 1] * 256 + $buffer[$pos] + 269;
				}

				$totDelta += $delta;

				if ($length === 13) {
					$pos++;
					$length = $buffer[$pos] + 13;

				} elseif ($length === 14) {
					$pos += 2;
					$length = $buffer[$pos - 1] * 256 + $buffer[$pos] + 269;
				}

				$value = '';
				for ($i = $pos + 1; $i <= $pos + $length; $i++) {
					$value .= pack('C', $buffer[$i]);
				}

				$pos = $pos + $length + 1;

				if ($totDelta === 3_332) {
					[
						$deviceType,
						$deviceIdentifier,
					] = explode('#', mb_convert_encoding($value, 'cp1252', 'utf8')) + [null, null];
				}

				$byte = $buffer[$pos];
			}

			$message = '';

			for ($i = $pos + 1; $i <= count($buffer); $i++) {
				$message .= pack('C', $buffer[$i]);
			}

			$message = mb_convert_encoding($message, 'cp1252', 'utf8');

			$this->logger->debug(
				sprintf(
					'Received message: CoAP Code: %d, Type: %s, Id: %s, Payload: %s',
					$code,
					$deviceType,
					$deviceIdentifier,
					str_replace(' ', '', $message),
				),
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY->value,
					'type' => 'gen1-coap-api',
				],
			);

			if (
				$this->isValidStatusMessage($message)
				&& $deviceType !== null
				&& $deviceIdentifier !== null
			) {
				try {
					$this->handleStatusMessage($deviceIdentifier, $message, $remote);
				} catch (Exceptions\CoapError | Exceptions\InvalidState $ex) {
					Utils\Arrays::invoke($this->onError, $ex);
				}
			}
		}
	}

	private function isValidStatusMessage(string $message): bool
	{
		try {
			$this->validatePayload($message, self::STATE_MESSAGE_SCHEMA_FILENAME);

		} catch (Exceptions\CoapError | Exceptions\InvalidState) {
			return false;
		}

		return true;
	}

	/**
	 * @throws Exceptions\CoapError
	 * @throws Exceptions\InvalidState
	 */
	private function handleStatusMessage(
		string $deviceIdentifier,
		string $message,
		string $remote,
	): void
	{
		if (
			array_key_exists($deviceIdentifier, $this->lastMessage)
			&& $this->lastMessage[$deviceIdentifier] === $message
		) {
			return;
		}

		$this->lastMessage[$deviceIdentifier] = $message;

		$parsedMessage = $this->validatePayload($message, self::STATE_MESSAGE_SCHEMA_FILENAME);

		if (
			!$parsedMessage->offsetExists('G')
			|| !$parsedMessage['G'] instanceof Utils\ArrayHash
		) {
			throw new Exceptions\CoapError('Provided message is not valid');
		}

		$statuses = [];

		foreach ($parsedMessage['G'] as $status) {
			if ((is_array($status) || $status instanceof Utils\ArrayHash) && count($status) === 3) {
				[$blockIdentifier, $sensorIdentifier, $sensorValue] = (array) $status;

				$statuses[] = [
					'block' => $blockIdentifier,
					'sensor' => $sensorIdentifier,
					'value' => $sensorValue,
				];
			}
		}

		try {
			Utils\Arrays::invoke(
				$this->onMessage,
				$this->messageBuilder->create(
					Messages\Response\Gen1\ReportDeviceState::class,
					[
						'identifier' => $deviceIdentifier,
						'ip_address' => preg_replace('/(:[0-9]+)+$/', '', $remote),
						'states' => $statuses,
					],
				),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\InvalidState('Could not map payload to message', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\CoapError
	 * @throws Exceptions\InvalidState
	 */
	private function validatePayload(
		string $payload,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (ApplicationExceptions\Logic | ApplicationExceptions\MalformedInput | ToolsExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\CoapError(
					'Could not validate received response payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'response' . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\InvalidState('Validation schema for payload could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

}
