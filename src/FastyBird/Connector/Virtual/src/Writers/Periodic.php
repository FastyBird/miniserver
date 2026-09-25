<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Writers;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function array_merge;
use function in_array;
use function is_bool;
use function React\Async\async;
use function React\Async\await;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Periodic implements Writer
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 2_500.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000.0;

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	/** @var array<string, array<string, DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped|DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped>>  */
	private array $properties = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Documents\Connectors\Connector $connector,
		protected readonly Helpers\MessageBuilder $messageBuilder,
		protected readonly Queue\Queue $queue,
		protected readonly Virtual\Logger $logger,
		protected readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		protected readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Clock\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedProperties = [];

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			if (!array_key_exists($device->getId()->toString(), $this->properties)) {
				$this->properties[$device->getId()->toString()] = [];
			}

			$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
			$findDevicePropertiesQuery->forDevice($device);
			$findDevicePropertiesQuery->settable(true);

			$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
				$findDevicePropertiesQuery,
				DevicesDocuments\Devices\Properties\Dynamic::class,
			);

			foreach ($properties as $property) {
				$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
			}

			$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceMappedProperties();
			$findDevicePropertiesQuery->forDevice($device);

			$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
				$findDevicePropertiesQuery,
				DevicesDocuments\Devices\Properties\Mapped::class,
			);

			foreach ($properties as $property) {
				$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
			}

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy(
				$findChannelsQuery,
				Documents\Channels\Channel::class,
			);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);
				$findChannelPropertiesQuery->settable(true);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findChannelPropertiesQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
				}

				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelMappedProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findChannelPropertiesQuery,
					DevicesDocuments\Channels\Properties\Mapped::class,
				);

				foreach ($properties as $property) {
					$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
				}
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
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->writeProperty($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 */
	private function writeProperty(Documents\Devices\Device $device): bool
	{
		$now = $this->clock->getNow();

		foreach ($this->properties[$device->getId()->toString()] as $property) {
			$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
				? $this->processedProperties[$property->getId()->toString()]
				: false;

			if (
				$debounce !== false
				&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
			) {
				continue;
			}

			$this->processedProperties[$property->getId()->toString()] = $now;

			if (
				$property instanceof DevicesDocuments\Devices\Properties\Dynamic
				|| $property instanceof DevicesDocuments\Devices\Properties\Mapped
			) {
				$state = await($this->devicePropertiesStatesManager->read(
					$property,
					Sources\Connector::VIRTUAL,
				));

				if (is_bool($state)) {
					// Property state was requested
					if ($state === true) {
						return true;
					}

					// Requesting property state failed
					continue;
				} elseif (!$state instanceof DevicesDocuments\States\Devices\Properties\Property) {
					// Property state is not set
					continue;
				}

				$propertyValue = $property instanceof DevicesDocuments\Devices\Properties\Dynamic
					? $state->getGet()->getExpectedValue()
					: $state->getRead()->getExpectedValue() ?? ($state->isValid() ? $state->getRead()->getActualValue() : null);
			} else {
				$state = await($this->channelPropertiesStatesManager->read(
					$property,
					Sources\Connector::VIRTUAL,
				));

				if (is_bool($state)) {
					// Property state was requested
					if ($state === true) {
						return true;
					}

					// Requesting property state failed
					continue;
				} elseif (!$state instanceof DevicesDocuments\States\Channels\Properties\Property) {
					// Property state is not set
					continue;
				}

				$propertyValue = $property instanceof DevicesDocuments\Channels\Properties\Dynamic
					? $state->getGet()->getExpectedValue()
					: $state->getRead()->getExpectedValue() ?? ($state->isValid() ? $state->getRead()->getActualValue() : null);
			}

			if ($propertyValue === null) {
				continue;
			}

			$pending = $state->getPending();

			if (
				(
					$property instanceof DevicesDocuments\Devices\Properties\Dynamic
					|| $property instanceof DevicesDocuments\Channels\Properties\Dynamic
				) && (
					$pending === true
					|| (
						$pending instanceof DateTimeInterface
						&& (float) $now->format('Uv') - (float) $pending->format('Uv') > self::HANDLER_PENDING_DELAY
					)
				)
			) {
				try {
					if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\WriteDevicePropertyState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'property' => $property->getId(),
									'state' => array_merge(
										$state->getGet()->toArray(),
										[
											'id' => $state->getId(),
											'valid' => $state->isValid(),
											'pending' => $state->getPending() instanceof DateTimeInterface
												? $state->getPending()->format(DateTimeInterface::ATOM)
												: $state->getPending(),
										],
									),
								],
							),
						);
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\WriteChannelPropertyState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'channel' => $property->getChannel(),
									'property' => $property->getId(),
									'state' => array_merge(
										$state->getGet()->toArray(),
										[
											'id' => $state->getId(),
											'valid' => $state->isValid(),
											'pending' => $state->getPending() instanceof DateTimeInterface
												? $state->getPending()->format(DateTimeInterface::ATOM)
												: $state->getPending(),
										],
									),
								],
							),
						);
					}
				} catch (Throwable $ex) {
					// Log caught exception
					$this->logger->error(
						'Characteristic value could not be prepared for writing',
						[
							'source' => Sources\Connector::VIRTUAL->value,
							'type' => 'periodic-writer',
							'exception' => Logging\Logger::buildException($ex),
						],
					);

					return false;
				}
			} elseif (
				$property instanceof DevicesDocuments\Devices\Properties\Mapped
				|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
			) {
				try {
					if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\WriteDevicePropertyState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'property' => $property->getId(),
									'state' => array_merge(
										$state->getRead()->toArray(),
										[
											'id' => $state->getId(),
											'valid' => $state->isValid(),
											'pending' => $state->getPending() instanceof DateTimeInterface
												? $state->getPending()->format(DateTimeInterface::ATOM)
												: $state->getPending(),
										],
									),
								],
							),
						);
					} else {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\WriteChannelPropertyState::class,
								[
									'connector' => $device->getConnector(),
									'device' => $device->getId(),
									'channel' => $property->getChannel(),
									'property' => $property->getId(),
									'state' => array_merge(
										$state->getRead()->toArray(),
										[
											'id' => $state->getId(),
											'valid' => $state->isValid(),
											'pending' => $state->getPending() instanceof DateTimeInterface
												? $state->getPending()->format(DateTimeInterface::ATOM)
												: $state->getPending(),
										],
									),
								],
							),
						);
					}

					return true;
				} catch (Throwable $ex) {
					// Log caught exception
					$this->logger->error(
						'Characteristic value could not be prepared for writing',
						[
							'source' => Sources\Connector::VIRTUAL->value,
							'type' => 'periodic-writer',
							'exception' => Logging\Logger::buildException($ex),
						],
					);

					return false;
				}
			}
		}

		return false;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
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
