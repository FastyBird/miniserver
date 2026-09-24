<?php declare(strict_types = 1);

/**
 * Local.php
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
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function in_array;
use function is_array;
use function React\Async\async;

/**
 * Local devices client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Local implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	private const CMD_STATE = 'state';

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];

	/** @var array<string, API\LocalApi> */
	private array $devicesClients = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|false>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Device $deviceHelper,
		private readonly Queue\Queue $queue,
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly Clock\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$this->processedDevices = [];

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			if ($this->deviceHelper->getGateway($device) === null) {
				$this->createDeviceClient($device);
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);
	}

	public function disconnect(): void
	{
		foreach ($this->devicesClients as $client) {
			$client->disconnect();
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleCommunication(): void
	{
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
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processDevice(Documents\Devices\Device $device): bool
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			if ($this->deviceHelper->getGateway($device) === null) {
				$this->createDeviceClient($device);

				return false;
			}

			return true;
		}

		if (!$client->isConnected()) {
			$deviceState = $this->deviceConnectionManager->getState($device);

			if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
				unset($this->devices[$device->getId()->toString()]);

				return false;
			}

			if (!$client->isConnecting()) {
				if (
					$client->getLastConnectAttempt() === null
					|| (
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						$this->clock->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
					)
				) {
					$client
						->connect()
						->then(function () use ($device): void {
							$this->logger->debug(
								'Connected to Tuya local cloud device',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'local-client',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);
						})
						->catch(function (Throwable $ex) use ($device): void {
							$this->logger->error(
								'Tuya local device client could not be created',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'local-client',
									'exception' => Logging\Logger::buildException($ex),
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);

							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector(),
										'identifier' => $device->getIdentifier(),
										'state' => DevicesTypes\ConnectionState::LOST,
									],
								),
							);
						});

				} else {
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
				}
			}

			return false;
		}

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

		$client->readStates($this->deviceHelper->getGateway($device) !== null ? $device->getIdentifier() : null)
			->then(
				function (array|API\Messages\Response\LocalDeviceWifiScan|Types\LocalDeviceError|string|null $statuses) use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

					if (is_array($statuses)) {
						$dataPointsStatuses = [];

						foreach ($statuses as $status) {
							if (!in_array($status->getCode(), $this->deviceHelper->getExcludedDps($device), true)) {
								$dataPointsStatuses[] = [
									'code' => $status->getCode(),
									'value' => $status->getValue(),
								];
							}
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'data_points' => $dataPointsStatuses,
								],
							),
						);
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);
				},
			)
			->catch(function (Throwable $ex) use ($device): void {
				if ($ex instanceof TuyaExceptions\LocalApiBusy || $ex instanceof TuyaExceptions\LocalApiTimeout) {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = false;

				} else {
					$this->logger->warning(
						'Could not call local api',
						[
							'source' => Sources\Connector::TUYA->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);

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
				}
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createDeviceClient(Documents\Devices\Device $device): void
	{
		if (array_key_exists($device->getId()->toString(), $this->devicesClients)) {
			throw new TuyaExceptions\InvalidState('Tuya local device client is already created');
		}

		unset($this->processedDevicesCommands[$device->getId()->toString()]);

		if (
			$this->deviceHelper->getGatewayId($device) !== null
			|| $this->deviceHelper->getNodeId($device) !== null
		) {
			return;
		}

		$client = $this->connectionManager->getLocalConnection($device);

		$client->onMessage[] = function (API\Messages\Message $message): void {
			if (
				$message instanceof API\Messages\Response\LocalDeviceMessage
				&& $message->getCommand() === Types\LocalDeviceCommand::STATUS
				&& is_array($message->getData())
			) {
				$dataPointsStatuses = [];

				foreach ($message->getData() as $dataPoint) {
					$dataPointsStatuses[] = [
						'code' => $dataPoint->getCode(),
						'value' => $dataPoint->getValue(),
					];
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $this->connector->getId(),
							'identifier' => $message->getIdentifier(),
							'data_points' => $dataPointsStatuses,
						],
					),
				);
			}
		};

		$client->onConnected[] = function () use ($device): void {
			$this->logger->debug(
				'Connected to device',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'local-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::CONNECTED,
					],
				),
			);
		};

		$client->onError[] = function (Throwable $ex) use ($device): void {
			$this->logger->warning(
				'An error occurred in Tuya local device client',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'local-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::LOST,
					],
				),
			);
		};

		$this->devicesClients[$device->getId()->toString()] = $client;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getDeviceClient(Documents\Devices\Device $device): API\LocalApi|null
	{
		$parent = $this->deviceHelper->getGateway($device);

		if ($parent !== null) {
			return array_key_exists(
				$parent->getId()->toString(),
				$this->devicesClients,
			)
				? $this->devicesClients[$parent->getId()->toString()]
				: null;
		}

		return array_key_exists(
			$device->getId()->toString(),
			$this->devicesClients,
		)
			? $this->devicesClients[$device->getId()->toString()]
			: null;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\Runtime
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
