<?php declare(strict_types = 1);

/**
 * Cloud.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use DateTimeInterface;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;
use function in_array;
use function React\Async\async;

/**
 * Cloud devices client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Cloud implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	public const REFRESH_SLEEP_DELAY = 300.0;

	private const CMD_STATE = 'state';

	private const CMD_HEARTBEAT = 'heartbeat';

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Device $deviceHelper,
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud API',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->catch(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud API client could not be created',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::TUYA,
						'Tuya cloud API client could not be created',
					),
				);
			});

		$wsClient = $this->connectionManager
			->getCloudWsConnection($this->connector);

		$wsClient->onMessage[] = function (API\Messages\Message $message): void {
			if (
				$message instanceof API\Messages\Response\ReportDeviceOnline
				|| $message instanceof API\Messages\Response\ReportDeviceState
			) {
				$knowDevices = array_filter(
					$this->devices,
					static fn (Documents\Devices\Device $device): bool => $device->getIdentifier() === $message->getIdentifier(),
				);

				if ($knowDevices === []) {
					return;
				}

				if ($message instanceof API\Messages\Response\ReportDeviceOnline) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $this->connector->getId(),
								'identifier' => $message->getIdentifier(),
								'state' => $message->isOnline()
									? DevicesTypes\ConnectionState::CONNECTED
									: DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				} else {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $this->connector->getId(),
								'identifier' => $message->getIdentifier(),
								'data_points' => array_map(
									static fn (API\Messages\Response\DataPointState $dps): array => [
										'code' => $dps->getCode(),
										'value' => $dps->getValue(),
									],
									$message->getDataPoints(),
								),
							],
						),
					);
				}
			}
		};

		$wsClient->onError[] = function (Throwable $ex): void {
			$this->logger->error(
				'An error occurred in Tuya cloud WS client',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'cloud-client',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					MetadataTypes\Sources\Connector::TUYA,
					'An error occurred in Tuya cloud WS client',
				),
			);
		};

		$wsClient
			->connect()
			->then(function (): void {
				$this->logger->debug(
					'Connected to Tuya cloud WS server',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			})
			->catch(function (Throwable $ex): void {
				$this->logger->error(
					'Tuya cloud WS client could not be created',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::TUYA,
						'Tuya cloud WS client could not be created',
					),
				);
			});
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		$this->connectionManager
			->getCloudWsConnection($this->connector)
			->disconnect();

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->disconnect();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleCommunication(): void
	{
		if (!$this->connectionManager->getCloudApiConnection($this->connector)->isConnected()) {
			$this->connectionManager->getCloudApiConnection($this->connector)->connect(false);
		}

		if ($this->connectionManager->getCloudApiConnection($this->connector)->isRefreshFailed()) {
			$refreshFailedAt = $this->connectionManager->getCloudApiConnection($this->connector)->getRefreshFailed();
			assert($refreshFailedAt instanceof DateTimeInterface);

			if ($this->clock->getNow()->getTimestamp() - $refreshFailedAt->getTimestamp() < self::REFRESH_SLEEP_DELAY) {
				return;
			}

			$this->connectionManager->getCloudApiConnection($this->connector)->connect(false);
		}

		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processDevice(Documents\Devices\Device $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readDeviceInformation(Documents\Devices\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getHeartbeatDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->clock->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceDetail($device->getIdentifier())
			->then(function (API\Messages\Response\GetDevice $detail) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->clock->getNow();

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => $detail->getResult()->isOnline()
								? DevicesTypes\ConnectionState::CONNECTED
								: DevicesTypes\ConnectionState::DISCONNECTED,
						],
					),
				);
			})
			->catch(function (Throwable $ex) use ($device): void {
				$renderException = true;

				if ($ex instanceof Exceptions\OpenApiError) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);

					$renderException = false;
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\Sources\Connector::TUYA,
							'Unhandled error occur',
						),
					);
				}

				$this->logger->error(
					'Could not call cloud openapi',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'exception' => ToolsHelpers\Logger::buildException($ex, $renderException),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			});

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readDeviceState(Documents\Devices\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp()
					< $this->deviceHelper->getStateReadingDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$this->connectionManager
			->getCloudApiConnection($this->connector)
			->getDeviceState($device->getIdentifier())
			->then(function (API\Messages\Response\GetDeviceState $state) use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $device->getConnector(),
							'identifier' => $device->getIdentifier(),
							'data_points' => array_map(
								static fn (API\Messages\Response\DeviceDataPointState $dps): array => [
									'code' => $dps->getCode(),
									'value' => $dps->getValue(),
								],
								$state->getResult(),
							),
						],
					),
				);
			})
			->catch(function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Calling Tuya cloud failed',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'cloud-client',
						'error' => $ex->getMessage(),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				if ($ex instanceof Exceptions\OpenApiError) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							MetadataTypes\Sources\Connector::TUYA,
							'Unhandled error occur',
						),
					);
				}
			});

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
