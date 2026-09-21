<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Devices;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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
use function React\Async\async;

/**
 * Devices service
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];

	/** @var array<string, Drivers\Driver> */
	private array $devicesDrivers = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface|false> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Drivers\DriversManager $driversManager,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Device $deviceHelper,
		private readonly Queue\Queue $queue,
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function start(): void
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
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);
	}

	public function stop(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}

		foreach ($this->devicesDrivers as $service) {
			$service->disconnect();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDevices(): void
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
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processDevice(Documents\Devices\Device $device): bool
	{
		$service = $this->driversManager->getDriver($device);

		if (!$service->isConnected()) {
			$deviceState = $this->deviceConnectionManager->getState($device);

			if (
				$deviceState === DevicesTypes\ConnectionState::ALERT
				|| $deviceState === DevicesTypes\ConnectionState::STOPPED
			) {
				unset($this->devices[$device->getId()->toString()]);

				return false;
			}

			if (!$service->isConnecting()) {
				if (
					$service->getLastConnectAttempt() === null
					|| (
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						$this->clock->getNow()->getTimestamp() - $service->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
					)
				) {
					$service
						->connect()
						->then(function () use ($device): void {
							$this->logger->debug(
								'Connected to virtual device',
								[
									'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
									'type' => 'devices-driver',
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
										'device' => $device->getId(),
										'state' => DevicesTypes\ConnectionState::CONNECTED,
										'source' => MetadataTypes\Sources\Connector::VIRTUAL,
									],
								),
							);
						})
						->catch(function (Throwable $ex) use ($device): void {
							$this->logger->error(
								'Virtual device service could not be created',
								[
									'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
									'type' => 'devices-driver',
									'exception' => ToolsHelpers\Logger::buildException($ex),
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
										'device' => $device->getId(),
										'state' => DevicesTypes\ConnectionState::ALERT,
										'source' => MetadataTypes\Sources\Connector::VIRTUAL,
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
								'device' => $device->getId(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								'source' => MetadataTypes\Sources\Connector::VIRTUAL,
							],
						),
					);
				}
			}

			return false;
		}

		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = false;
		}

		$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()];

		if (
			$cmdResult instanceof DateTimeInterface
			&& (
				$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp()
				< $this->deviceHelper->getStateProcessingDelay($device)
			)
		) {
			return false;
		}

		$this->processedDevicesCommands[$device->getId()->toString()] = $this->clock->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$service->process()
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()] = $this->clock->getNow();
			})
			->catch(function (Throwable $ex) use ($device, $service): void {
				$this->processedDevicesCommands[$device->getId()->toString()] = false;

				$this->logger->warning(
					'Could not call local api',
					[
						'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
						'type' => 'devices-driver',
						'exception' => ToolsHelpers\Logger::buildException($ex),
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
							'device' => $device->getId(),
							'state' => DevicesTypes\ConnectionState::ALERT,
							'source' => MetadataTypes\Sources\Connector::VIRTUAL,
						],
					),
				);

				$service->disconnect();
			});

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
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
				$this->handleDevices();
			}),
		);
	}

}
