<?php declare(strict_types = 1);

/**
 * OpenPulsar.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use Closure;
use DateTimeInterface;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as ToolsExceptions;
use FastyBird\Core\Schemas\Tools as ToolsSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Nette\Utils;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_keys;
use function base64_decode;
use function is_bool;
use function is_numeric;
use function is_string;
use function md5;
use function openssl_decrypt;
use function React\Async\async;
use function strval;
use const DIRECTORY_SEPARATOR;
use const OPENSSL_RAW_DATA;

/**
 * OpenPulsar interface
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class OpenPulsar
{

	use Nette\SmartObject;

	private const PING_INTERVAL = 30;

	public const WS_MESSAGE_SCHEMA_FILENAME = 'openpulsar_message.json';

	public const WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME = 'openpulsar_payload.json';

	public const WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME = 'openpulsar_data.json';

	/** @var array<Closure(): void> */
	public array $onConnected = [];

	/** @var array<Closure(): void> */
	public array $onDisconnected = [];

	/** @var array<Closure(): void> */
	public array $onLost = [];

	/** @var array<Closure(Messages\Message $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	private bool $connecting = false;

	private bool $connected = false;

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private EventLoop\TimerInterface|null $pingTimer = null;

	private Ratchet\Client\WebSocket|null $wsConnection = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string $accessId,
		private readonly string $accessSecret,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Types\OpenPulsarTopic $topic,
		private readonly Types\OpenPulsarEndpoint $endpoint,
		private readonly Tuya\Logger $logger,
		private readonly Services\WebSocketClientFactory $webSocketClientFactory,
		private readonly ToolsSchemas\Validator $schemaValidator,
		private readonly Clock\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface
	{
		if ($this->isConnected()) {
			return Promise\resolve(true);
		}

		$this->pingTimer = null;

		$this->wsConnection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->lastConnectAttempt = $this->clock->getNow();
		$this->lost = null;
		$this->disconnected = null;

		$deferred = new Promise\Deferred();

		$this->webSocketClientFactory->create(
			$this->buildWsTopicUrl(),
			$this->accessId,
			$this->generatePassword(),
		)
			->then(function (Ratchet\Client\WebSocket $connection) use ($deferred): void {
				$this->wsConnection = $connection;
				$this->connecting = false;
				$this->connected = true;

				$this->lost = null;
				$this->disconnected = null;

				$this->logger->debug(
					'Connected to Tuya sockets server',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'openpulsar-api',
						'connector' => [
							'identifier' => $this->identifier,
						],
					],
				);

				$connection->on('message', function (RFC6455\Messaging\MessageInterface $message): void {
					try {
						$this->handleWsMessage($message->getPayload());
					} catch (Exceptions\OpenPulsarError $ex) {
						Utils\Arrays::invoke($this->onError, $ex);
					}
				});

				$connection->on('error', function (Throwable $ex): void {
					$this->lost();

					Utils\Arrays::invoke($this->onError, $ex);
				});

				$connection->on('close', function ($code = null, $reason = null): void {
					$this->logger->debug(
						'Connection to Tuya WS server was closed',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'openpulsar-api',
							'connection' => [
								'code' => $code,
								'reason' => $reason,
							],
							'connector' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$this->disconnect();

					Utils\Arrays::invoke($this->onDisconnected);
				});

				$this->pingTimer = $this->eventLoop->addPeriodicTimer(
					self::PING_INTERVAL,
					async(function () use ($connection): void {
						$connection->send(new RFC6455\Messaging\Frame(
							$this->accessId,
							true,
							RFC6455\Messaging\Frame::OP_PING,
						));
					}),
				);

				Utils\Arrays::invoke($this->onConnected);

				$deferred->resolve(true);
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				$this->wsConnection = null;

				$this->connecting = false;
				$this->connected = false;

				$deferred->reject(
					new Exceptions\OpenPulsarError('Connection to Tuya WS server failed', $ex->getCode(), $ex),
				);
			});

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->wsConnection?->close();
		$this->wsConnection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->clock->getNow();

		if ($this->pingTimer !== null) {
			$this->eventLoop->cancelTimer($this->pingTimer);

			$this->pingTimer = null;
		}
	}

	public function isConnecting(): bool
	{
		return $this->connecting;
	}

	public function isConnected(): bool
	{
		return $this->wsConnection !== null && !$this->connecting && $this->connected;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->lastConnectAttempt;
	}

	public function getDisconnected(): DateTimeInterface|null
	{
		return $this->disconnected;
	}

	public function getLost(): DateTimeInterface|null
	{
		return $this->lost;
	}

	private function lost(): void
	{
		$this->lost = $this->clock->getNow();

		Utils\Arrays::invoke($this->onLost);

		$this->disconnect();
	}

	/**
	 * @throws Exceptions\OpenPulsarError
	 */
	private function handleWsMessage(string $message): void
	{
		try {
			$message = $this->schemaValidator->validate(
				$message,
				$this->getSchema(self::WS_MESSAGE_SCHEMA_FILENAME),
			);

		} catch (ApplicationExceptions\Logic | ApplicationExceptions\MalformedInput | ToolsExceptions\InvalidData | Exceptions\OpenPulsarError $ex) {
			throw new Exceptions\OpenPulsarError('Could not decode received Tuya WS message', $ex->getCode(), $ex);
		}

		if ($this->wsConnection !== null && $message->offsetExists('messageId')) {
			try {
				/**
				 * Confirm received message
				 * Received message have to confirmed to be removed from queue on Tuya server side
				 */
				$this->wsConnection->send(Utils\Json::encode(['messageId' => $message->offsetGet('messageId')]));

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\OpenPulsarError('Could not confirm received Tuya WS message', $ex->getCode(), $ex);
			}
		}

		if (!$message->offsetExists('payload')) {
			throw new Exceptions\OpenPulsarError('Received Tuya WS message is invalid');
		}

		$payload = base64_decode(strval($message->offsetGet('payload')), true);

		if ($payload === false) {
			throw new Exceptions\OpenPulsarError('Received Tuya WS message payload could not be decoded');
		}

		$this->logger->debug(
			'Received message origin payload',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
				'type' => 'openpulsar-api',
				'data' => [
					'payload' => $payload,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			],
		);

		try {
			$payload = $this->schemaValidator->validate(
				$payload,
				$this->getSchema(self::WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME),
			);

		} catch (ApplicationExceptions\Logic | ApplicationExceptions\MalformedInput | ToolsExceptions\InvalidData | Exceptions\OpenPulsarError $ex) {
			throw new Exceptions\OpenPulsarError(
				'Could not decode received Tuya WS message payload',
				$ex->getCode(),
				$ex,
			);
		}

		if (!$payload->offsetExists('data')) {
			throw new Exceptions\OpenPulsarError('Could not decode received Tuya WS message payload');
		}

		$data = base64_decode(strval($payload->offsetGet('data')), true);

		if ($data === false) {
			throw new Exceptions\OpenPulsarError('Received Tuya WS message payload data could not be decoded');
		}

		$decodingKey = Utils\Strings::substring($this->accessSecret, 8, 16);

		$decryptedData = openssl_decrypt(
			$data,
			'AES-128-ECB',
			$decodingKey,
			OPENSSL_RAW_DATA,
		);

		if ($decryptedData === false) {
			throw new Exceptions\OpenPulsarError('Received Tuya WS message payload data could not be decrypted');
		}

		$this->logger->debug(
			'Received message decrypted',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
				'type' => 'openpulsar-api',
				'data' => $decryptedData,
				'connector' => [
					'identifier' => $this->identifier,
				],
			],
		);

		try {
			$decryptedData = $this->schemaValidator->validate(
				$decryptedData,
				$this->getSchema(self::WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME),
			);

		} catch (ApplicationExceptions\Logic | ApplicationExceptions\MalformedInput | ToolsExceptions\InvalidData | Exceptions\OpenPulsarError $ex) {
			throw new Exceptions\OpenPulsarError(
				'Could not decode received Tuya WS message payload data decrypted',
				$ex->getCode(),
				$ex,
			);
		}

		if (
			$decryptedData->offsetExists('devId')
			&& $decryptedData->offsetExists('status')
			&& $decryptedData->offsetGet('status') instanceof Utils\ArrayHash
		) {
			$dataPointsStatuses = [];

			foreach ($decryptedData->status as $status) {
				if (
					!$status instanceof Utils\ArrayHash
					|| !$status->offsetExists('code')
					|| !$status->offsetExists('value')
				) {
					continue;
				}

				if (is_string($status->value) || is_bool($status->value) || is_numeric($status->value)) {
					$dataPointsStatus = [
						'code' => $status->offsetGet('code'),
						'value' => $status->offsetGet('value'),
						'dps' => $status->offsetExists('dps') ? $status->offsetGet('dps') : null,
					];

					foreach (array_keys((array) $status) as $key) {
						if (is_numeric($key)) {
							$dataPointsStatus['dps'] = strval($key);
						}
					}

					$dataPointsStatuses[] = $dataPointsStatus;
				}
			}

			try {
				Utils\Arrays::invoke(
					$this->onMessage,
					$this->messageBuilder->create(
						Messages\Response\ReportDeviceState::class,
						[
							'identifier' => $decryptedData->devId,
							'data_points' => $dataPointsStatuses,
						],
					),
				);
			} catch (Exceptions\Runtime $ex) {
				throw new Exceptions\OpenPulsarError(
					'An error occurred, received device data points status could not be converted to message',
					$ex->getCode(),
					$ex,
				);
			}

			return;
		}

		if (
			$decryptedData->offsetExists('bizCode')
			&& (
				$decryptedData->offsetGet('bizCode') === Types\OpenPulsarMessageType::ONLINE->value
				|| $decryptedData->offsetGet('bizCode') === Types\OpenPulsarMessageType::OFFLINE->value
			)
		) {
			try {
				Utils\Arrays::invoke(
					$this->onMessage,
					$this->messageBuilder->create(
						Messages\Response\ReportDeviceOnline::class,
						[
							'identifier' => $decryptedData->devId,
							'online' => $decryptedData->offsetGet(
								'bizCode',
							) === Types\OpenPulsarMessageType::ONLINE->value,
						],
					),
				);
			} catch (Exceptions\Runtime $ex) {
				throw new Exceptions\OpenPulsarError(
					'An error occurred, received device online status could not be converted to message',
					$ex->getCode(),
					$ex,
				);
			}
		}
	}

	private function buildWsTopicUrl(): string
	{
		return $this->endpoint->value . 'ws/v2/consumer/persistent/'
			. $this->accessId . '/out/' . $this->topic->value . '/'
			. $this->accessId . '-sub?ackTimeoutMillis=3000&subscriptionType=Failover';
	}

	private function generatePassword(): string
	{
		$passString = $this->accessId . md5($this->accessSecret);

		return Utils\Strings::substring(md5($passString), 8, 16);
	}

	/**
	 * @throws Exceptions\OpenPulsarError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\OpenPulsarError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

}
