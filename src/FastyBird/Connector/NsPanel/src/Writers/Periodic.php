<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Writers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_merge;
use function in_array;
use function is_bool;
use function React\Async\async;
use function React\Async\await;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:NsPanelConnector!
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

	/** @var array<string, array<string, DevicesDocuments\Channels\Properties\Property>>  */
	private array $properties = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Documents\Connectors\Connector $connector,
		protected readonly Helpers\MessageBuilder $messageBuilder,
		protected readonly Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		protected readonly Queue\Queue $queue,
		protected readonly NsPanel\Logger $logger,
		protected readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		protected readonly DateTimeFactory\Clock $clock,
		protected readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
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
			if (
				!$device instanceof Documents\Devices\SubDevice
				&& !$device instanceof Documents\Devices\ThirdPartyDevice
			) {
				continue;
			}

			$this->devices[$device->getId()->toString()] = $device;

			if (!array_key_exists($device->getId()->toString(), $this->properties)) {
				$this->properties[$device->getId()->toString()] = [];
			}

			$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
				$findChannelPropertiesQuery->forChannel($channel);
				$findChannelPropertiesQuery->settable(true);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

				foreach ($properties as $property) {
					if (
						(
							$device instanceof Documents\Devices\SubDevice
							&& $property instanceof DevicesDocuments\Channels\Properties\Dynamic
						) || (
							$device instanceof Documents\Devices\ThirdPartyDevice
							&& (
								$property instanceof DevicesDocuments\Channels\Properties\Mapped
								|| $property instanceof DevicesDocuments\Channels\Properties\Dynamic
								|| $property instanceof DevicesDocuments\Channels\Properties\Variable
							)
						)
					) {
						$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
					}
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function writeProperty(Documents\Devices\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->properties)) {
			return false;
		}

		foreach ($this->properties[$device->getId()->toString()] as $property) {
			if (
				$device instanceof Documents\Devices\SubDevice
				&& $property instanceof DevicesDocuments\Channels\Properties\Dynamic
			) {
				if ($this->writeSubDeviceChannelProperty($device, $property)) {
					return true;
				}
			} elseif ($device instanceof Documents\Devices\ThirdPartyDevice) {
				if ($this->writeThirdPartyDeviceChannelProperty($device, $property)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function writeSubDeviceChannelProperty(
		Documents\Devices\SubDevice $device,
		DevicesDocuments\Channels\Properties\Dynamic $property,
	): bool
	{
		$now = $this->clock->getNow();

		$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
			? $this->processedProperties[$property->getId()->toString()]
			: false;

		if (
			$debounce !== false
			&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
		) {
			return false;
		}

		$this->processedProperties[$property->getId()->toString()] = $now;

		$state = await($this->channelPropertiesStatesManager->read(
			$property,
			MetadataTypes\Sources\Connector::NS_PANEL,
		));

		if (is_bool($state)) {
			return $state;
		} elseif (!$state instanceof DevicesDocuments\States\Channels\Properties\Property) {
			// Property state is not set
			return false;
		}

		if ($state->getGet()->getExpectedValue() === null) {
			return false;
		}

		$pending = $state->getPending();

		if (
			$pending === true
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') > self::HANDLER_PENDING_DELAY
			)
		) {
			try {
				$this->processedProperties[$property->getId()->toString()] = $now;

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteSubDeviceState::class,
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

				return true;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Characteristic value could not be prepared for writing',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'periodic-writer',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return false;
			}
		}

		return false;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function writeThirdPartyDeviceChannelProperty(
		Documents\Devices\ThirdPartyDevice $device,
		DevicesDocuments\Channels\Properties\Property $property,
	): bool
	{
		$now = $this->clock->getNow();

		$serialNumber = $this->thirdPartyDeviceHelper->getGatewayIdentifier($device);

		if ($serialNumber === null) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
			? $this->processedProperties[$property->getId()->toString()]
			: false;

		if (
			$debounce !== false
			&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
		) {
			return false;
		}

		$this->processedProperties[$property->getId()->toString()] = $now;

		if ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$state = await($this->channelPropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Connector::NS_PANEL,
			));

			if (is_bool($state)) {
				return $state;
			} elseif (!$state instanceof DevicesDocuments\States\Channels\Properties\Property) {
				// Property state is not set
				return false;
			}

			$propertyValue = $state->getGet()->getExpectedValue() ?? ($state->isValid() ? $state->getGet()->getActualValue() : null);

			if ($propertyValue === null) {
				return false;
			}

			try {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteThirdPartyDeviceState::class,
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

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Characteristic value could not be prepared for writing',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'periodic-writer',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return false;
			}
		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$state = await($this->channelPropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Connector::NS_PANEL,
			));

			if (is_bool($state)) {
				return $state;
			} elseif (!$state instanceof DevicesDocuments\States\Channels\Properties\Property) {
				// Property state is not set
				return false;
			}

			$propertyValue = $state->getGet()->getExpectedValue() ?? ($state->isValid() ? $state->getGet()->getActualValue() : null);

			if ($propertyValue === null) {
				return false;
			}

			try {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteThirdPartyDeviceState::class,
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

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Characteristic value could not be prepared for writing',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'periodic-writer',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return false;
			}
		} else {
			try {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\WriteThirdPartyDeviceState::class,
						[
							'connector' => $device->getConnector(),
							'device' => $device->getId(),
							'channel' => $property->getChannel(),
							'property' => $property->getId(),
						],
					),
				);

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Characteristic value could not be prepared for writing',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'periodic-writer',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return false;
			}
		}

		return false;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
