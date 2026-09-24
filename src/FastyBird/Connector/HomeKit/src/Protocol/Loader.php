<?php declare(strict_types = 1);

/**
 * Driver.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit\Protocol;

use Composer;
use Doctrine\DBAL;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities as ValuesUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Hashids;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use z4kn4fein\SemVer;
use function array_intersect;
use function array_map;
use function array_values;
use function assert;
use function floatval;
use function intval;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function str_split;
use function strval;
use function ucwords;
use function usort;

/**
 * HAP accessory driver service
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Loader
{

	private Hashids\Hashids $hashIds;

	/**
	 * @param array<Protocol\Accessories\AccessoryFactory> $accessoryFactories
	 * @param array<Protocol\Services\ServiceFactory> $serviceFactories
	 * @param array<Protocol\Characteristics\CharacteristicFactory> $characteristicsFactories
	 *
	 * @throws Hashids\HashidsException
	 */
	public function __construct(
		private readonly Protocol\Driver $accessoriesDriver,
		private readonly Protocol\Accessories\BridgeFactory $bridgeAccessoryFactory,
		private readonly array $accessoryFactories,
		private readonly array $serviceFactories,
		private readonly array $characteristicsFactories,
		private readonly HomeKitHelpers\MessageBuilder $messageBuilder,
		private readonly HomeKitHelpers\Device $deviceHelper,
		private readonly HomeKitHelpers\Channel $channelHelper,
		private readonly HomeKitHelpers\Loader $loader,
		private readonly Queue\Queue $queue,
		private readonly HomeKit\Logger $logger,
		private readonly PersistenceHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
		$this->hashIds = new Hashids\Hashids();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws HomeKitExceptions\Runtime
	 * @throws Nette\IOException
	 * @throws SemVer\SemverException
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function load(Documents\Connectors\Connector $connector): void
	{
		$bridge = $this->buildAccessory(
			$connector,
			null,
			HomeKitTypes\AccessoryCategory::BRIDGE,
		);
		assert($bridge instanceof Protocol\Accessories\Bridge);

		$this->accessoriesDriver->reset();
		$this->accessoriesDriver->addBridge($bridge);

		$bridgedAccessories = [];

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::AID);

			$aidProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesDocuments\Devices\Properties\Variable::class,
			);

			$aid = $aidProperty?->getValue() ?? null;

			if ($aid !== null) {
				$aid = intval(ValuesUtilities\Value::flattenValue($aid));
			}

			$accessory = $this->buildAccessory(
				$device,
				$aid,
				$this->deviceHelper->getAccessoryCategory($device),
			);
			assert($accessory instanceof Protocol\Accessories\Generic);

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy(
				$findChannelsQuery,
				Documents\Channels\Channel::class,
			);

			foreach ($channels as $channel) {
				$service = $this->buildService(
					$this->channelHelper->getServiceType($channel),
					$accessory,
					$channel,
				);

				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

				foreach ($properties as $property) {
					$format = $property->getFormat();

					$characteristic = $this->buildCharacteristic(
						HomeKitTypes\ChannelPropertyIdentifier::from($property->getIdentifier()),
						$service,
						$property,
						$format instanceof Formats\StringEnum
							? array_map(static fn (string $item): int => intval($item), $format->toArray())
							: null,
						null,
						$format instanceof Formats\NumberRange ? $format->getMin() : null,
						$format instanceof Formats\NumberRange ? $format->getMax() : null,
						$property->getStep(),
						$property->getUnit() !== null && HomeKitTypes\CharacteristicUnit::tryFrom(
							$property->getUnit(),
						) !== null
							? HomeKitTypes\CharacteristicUnit::from($property->getUnit())
							: null,
					);

					$service->addCharacteristic($characteristic);
				}

				$accessory->addService($service);
			}

			$bridgedAccessories[] = $accessory;
		}

		usort(
			$bridgedAccessories,
			static function (Protocol\Accessories\Generic $a, Protocol\Accessories\Generic $b) {
				if ($a->getAid() === null) {
					return 1;
				}

				if ($b->getAid() === null) {
					return -1;
				}

				if ($a->getAid() === $b->getAid()) {
					return 0;
				}

				return $a->getAid() <=> $b->getAid();
			},
		);

		foreach ($bridgedAccessories as $accessory) {
			$this->accessoriesDriver->addBridgedAccessory($accessory);

			$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
			$findDevicePropertyQuery->forDevice($accessory->getDevice());
			$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::AID);

			$aidProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesDocuments\Devices\Properties\Variable::class,
			);

			if ($aidProperty === null) {
				$this->databaseHelper->transaction(
					function () use ($accessory): void {
						$device = $this->devicesRepository->find(
							$accessory->getDevice()->getId(),
							Entities\Devices\Device::class,
						);
						assert($device instanceof Entities\Devices\Device);

						$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'identifier' => HomeKitTypes\DevicePropertyIdentifier::AID->value,
							'name' => DevicesUtilities\Name::createName(
								HomeKitTypes\DevicePropertyIdentifier::AID->value,
							),
							'dataType' => ValuesTypes\DataType::UCHAR,
							'value' => $accessory->getAid(),
							'device' => $device,
						]));
					},
				);
			}

			foreach ($accessory->getServices() as $service) {
				foreach ($service->getCharacteristics() as $characteristic) {
					$property = $characteristic->getProperty();

					if ($property !== null) {
						$characteristic->setActualValue($property->getDefault());
					}

					if ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
						$characteristic->setActualValue($property->getValue());
						$characteristic->setValid(true);
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
						try {
							$state = $this->channelPropertiesStatesManager->read(
								$property,
								Sources\Connector::HOMEKIT,
							);

							if (
								($state === null || is_bool($state) || $state->getGet()->getActualValue() === null)
								&& $property->getDefault() !== null
							) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreChannelPropertyState::class,
										[
											'connector' => $accessory->getDevice()->getConnector(),
											'device' => $accessory->getDevice()->getId(),
											'channel' => $service->getChannel()?->getId(),
											'property' => $property->getId(),
											'value' => $property->getDefault(),
										],
									),
								);

								$characteristic->setActualValue($property->getDefault());
								$characteristic->setValid(true);
							} elseif ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreChannelPropertyState::class,
										[
											'connector' => $accessory->getDevice()->getConnector(),
											'device' => $accessory->getDevice()->getId(),
											'channel' => $service->getChannel()?->getId(),
											'property' => $property->getId(),
											'value' => $state->getGet()->getExpectedValue() ?? $state->getGet()->getActualValue(),
										],
									),
								);

								$characteristic->setActualValue($state->getGet()->getActualValue());
								$characteristic->setExpectedValue($state->getGet()->getExpectedValue());
								$characteristic->setValid($state->isValid());
							}
						} catch (HomeKitExceptions\InvalidState $ex) {
							$this->logger->warning(
								'State value could not be set to characteristic',
								[
									'source' => Sources\Connector::HOMEKIT->value,
									'type' => 'http-server',
									'exception' => Logging\Logger::buildException($ex),
									'connector' => [
										'id' => $connector->getId()->toString(),
									],
									'device' => [
										'id' => $accessory->getDevice()->getId()->toString(),
									],
									'channel' => [
										'id' => $service->getChannel()?->getId()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
									],
								],
							);
						} catch (DevicesExceptions\NotImplemented) {
							// Ignore error
						}
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
						$findParentPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
						$findParentPropertyQuery->byId($property->getParent());

						$parent = $this->channelsPropertiesConfigurationRepository->findOneBy($findParentPropertyQuery);

						if ($parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
							try {
								$state = $this->channelPropertiesStatesManager->read(
									$property,
									Sources\Connector::HOMEKIT,
								);

								if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
									$characteristic->setActualValue($state->getRead()->getActualValue());
									$characteristic->setExpectedValue($state->getRead()->getExpectedValue());
									$characteristic->setValid($state->isValid());
								}
							} catch (HomeKitExceptions\InvalidState $ex) {
								$this->logger->warning(
									'State value could not be set to characteristic',
									[
										'source' => Sources\Connector::HOMEKIT->value,
										'type' => 'http-server',
										'exception' => Logging\Logger::buildException($ex),
										'connector' => [
											'id' => $connector->getId()->toString(),
										],
										'device' => [
											'id' => $accessory->getDevice()->getId()->toString(),
										],
										'channel' => [
											'id' => $service->getChannel()?->getId()->toString(),
										],
										'property' => [
											'id' => $property->getId()->toString(),
										],
									],
								);
							} catch (DevicesExceptions\NotImplemented) {
								// Ignore error
							}
						} elseif ($parent instanceof DevicesDocuments\Channels\Properties\Variable) {
							$characteristic->setActualValue($parent->getValue());
							$characteristic->setValid(true);
						}
					}
				}

				$service->recalculateCharacteristics();

				if ($service->getChannel() !== null) {
					foreach ($service->getCharacteristics() as $characteristic) {
						if ($characteristic->getProperty() === null) {
							continue;
						}

						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $accessory->getDevice()->getConnector(),
									'device' => $accessory->getDevice()->getId(),
									'channel' => $service->getChannel()->getId(),
									'property' => $characteristic->getProperty()->getId(),
									'value' => ValuesUtilities\Value::flattenValue($characteristic->getValue()),
								],
							),
						);
					}
				}
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws SemVer\SemverException
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function buildAccessory(
		Documents\Connectors\Connector|Documents\Devices\Device $owner,
		int|null $aid = null,
		HomeKitTypes\AccessoryCategory|null $category = null,
	): Protocol\Accessories\Accessory
	{
		$category ??= HomeKitTypes\AccessoryCategory::OTHER;

		if ($category === HomeKitTypes\AccessoryCategory::BRIDGE) {
			if (!$owner instanceof Documents\Connectors\Connector) {
				throw new HomeKitExceptions\InvalidArgument(
					'Bridge accessory owner have to be connector item instance',
				);
			}

			$accessory = $this->bridgeAccessoryFactory->create($owner->getName() ?? $owner->getIdentifier(), $owner);
		} else {
			if (!$owner instanceof Documents\Devices\Device) {
				throw new HomeKitExceptions\InvalidArgument('Device accessory owner have to be device item instance');
			}

			$accessory = null;

			foreach ($this->accessoryFactories as $accessoryFactory) {
				if ($owner::getType() === $accessoryFactory->getEntityClass()::getType()) {
					$accessory = $accessoryFactory->create(
						$owner->getName() ?? $owner->getIdentifier(),
						$aid,
						$category,
						$owner,
					);

					break;
				}
			}

			if ($accessory === null) {
				throw new HomeKitExceptions\InvalidState('Accessory could not be created');
			}
		}

		/**
		 * ACCESSORY INFORMATION SERVICE
		 */

		$accessoryInformation = $this->buildService(
			HomeKitTypes\ServiceType::ACCESSORY_INFORMATION,
			$accessory,
		);

		// NAME CHARACTERISTIC
		$accessoryName = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::NAME,
			$accessoryInformation,
		);
		$accessoryName->setActualValue($owner->getName() ?? $owner->getIdentifier());

		$accessoryInformation->addCharacteristic($accessoryName);

		// SERIAL NUMBER
		$accessorySerialNumber = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::SERIAL_NUMBER,
			$accessoryInformation,
		);

		if ($owner instanceof Documents\Devices\Device) {
			$serialNumber = $this->deviceHelper->getSerialNumber($owner);

			if ($serialNumber === null) {
				$serialNumber = $this->hashIds->encode(
					...array_map(
						static fn (string $part): int => intval($part),
						str_split($owner->getId()->getInteger()->toString(), 5),
					),
				);

				$this->databaseHelper->transaction(
					function () use ($owner, $serialNumber): void {
						$device = $this->devicesRepository->find(
							$owner->getId(),
							Entities\Devices\Device::class,
						);
						assert($device instanceof Entities\Devices\Device);

						$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'identifier' => HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
							'name' => DevicesUtilities\Name::createName(
								HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
							),
							'dataType' => ValuesTypes\DataType::STRING,
							'value' => $serialNumber,
							'device' => $device,
						]));
					},
				);
			}

			$accessorySerialNumber->setActualValue($serialNumber);
		} else {
			$accessorySerialNumber->setActualValue(
				$this->hashIds->encode(
					...array_map(
						static fn (string $part): int => intval($part),
						str_split($owner->getId()->getInteger()->toString(), 5),
					),
				),
			);
		}

		$accessoryInformation->addCharacteristic($accessorySerialNumber);

		// FIRMWARE REVISION
		$accessoryFirmwareRevision = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::FIRMWARE_REVISION,
			$accessoryInformation,
		);

		if ($owner instanceof Documents\Devices\Device) {
			$firmwareVersion = $this->deviceHelper->getFirmwareVersion($owner);

			if ($firmwareVersion === null) {
				$firmwareVersion = SemVer\Version::parse('1.0.0');

				$this->databaseHelper->transaction(
					function () use ($owner, $firmwareVersion): void {
						$device = $this->devicesRepository->find(
							$owner->getId(),
							Entities\Devices\Device::class,
						);
						assert($device instanceof Entities\Devices\Device);

						$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'identifier' => HomeKitTypes\DevicePropertyIdentifier::VERSION->value,
							'name' => DevicesUtilities\Name::createName(
								HomeKitTypes\DevicePropertyIdentifier::VERSION->value,
							),
							'dataType' => ValuesTypes\DataType::STRING,
							'value' => strval($firmwareVersion),
							'device' => $device,
						]));
					},
				);
			}

			$accessoryFirmwareRevision->setActualValue(strval($firmwareVersion));
		} else {
			$packageRevision = Composer\InstalledVersions::getVersion(HomeKit\Constants::PACKAGE_NAME);

			$accessoryFirmwareRevision->setActualValue(
				$packageRevision !== null && preg_match(
					HomeKit\Constants::VERSION_REGEXP,
					$packageRevision,
				) === 1 ? $packageRevision : '0.0.0',
			);
		}

		$accessoryInformation->addCharacteristic($accessoryFirmwareRevision);

		// MANUFACTURER
		$accessoryManufacturer = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::MANUFACTURER,
			$accessoryInformation,
		);

		if ($owner instanceof Documents\Devices\Device) {
			$manufacturer = $this->deviceHelper->getManufacturer($owner);

			if ($manufacturer === null) {
				$manufacturer = HomeKit\Constants::DEFAULT_MANUFACTURER;

				$this->databaseHelper->transaction(
					function () use ($owner, $manufacturer): void {
						$device = $this->devicesRepository->find(
							$owner->getId(),
							Entities\Devices\Device::class,
						);
						assert($device instanceof Entities\Devices\Device);

						$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'identifier' => HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
							'name' => DevicesUtilities\Name::createName(
								HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
							),
							'dataType' => ValuesTypes\DataType::STRING,
							'value' => $manufacturer,
							'device' => $device,
						]));
					},
				);
			}

			$accessoryManufacturer->setActualValue($manufacturer);
		} else {
			$accessoryManufacturer->setActualValue(HomeKit\Constants::DEFAULT_MANUFACTURER);
		}

		$accessoryInformation->addCharacteristic($accessoryManufacturer);

		// MODEL NAME
		$accessoryModel = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::MODEL,
			$accessoryInformation,
		);

		if ($owner instanceof Documents\Devices\Device) {
			$model = $this->deviceHelper->getModel($owner);

			if ($model === null) {
				$model = HomeKit\Constants::DEFAULT_DEVICE_MODEL;

				$this->databaseHelper->transaction(
					function () use ($owner, $model): void {
						$device = $this->devicesRepository->find(
							$owner->getId(),
							Entities\Devices\Device::class,
						);
						assert($device instanceof Entities\Devices\Device);

						$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'identifier' => HomeKitTypes\DevicePropertyIdentifier::MODEL->value,
							'name' => DevicesUtilities\Name::createName(
								HomeKitTypes\DevicePropertyIdentifier::MODEL->value,
							),
							'dataType' => ValuesTypes\DataType::STRING,
							'value' => $model,
							'device' => $device,
						]));
					},
				);
			}

			$accessoryModel->setActualValue($model);
		} else {
			$accessoryModel->setActualValue(HomeKit\Constants::DEFAULT_BRIDGE_MODEL);
		}

		$accessoryInformation->addCharacteristic($accessoryModel);

		// IDENTIFY SUPPORT
		$accessoryIdentify = $this->buildCharacteristic(
			HomeKitTypes\ChannelPropertyIdentifier::IDENTIFY,
			$accessoryInformation,
		);
		$accessoryIdentify->setActualValue(false);

		$accessoryInformation->addCharacteristic($accessoryIdentify);

		$accessory->addService($accessoryInformation);

		if ($accessory instanceof Protocol\Accessories\Bridge) {
			$accessoryProtocolInformation = new Protocol\Services\Service(
				Uuid\Uuid::fromString(Protocol\Services\Service::HAP_PROTOCOL_INFORMATION_SERVICE_UUID),
				HomeKitTypes\ServiceType::PROTOCOL_INFORMATION,
				$accessory,
				null,
				['Version'],
			);

			$accessoryProtocolVersion = $this->buildCharacteristic(
				HomeKitTypes\ChannelPropertyIdentifier::VERSION,
				$accessoryProtocolInformation,
			);
			$accessoryProtocolVersion->setActualValue(HomeKit\Constants::HAP_PROTOCOL_VERSION);

			$accessoryProtocolInformation->addCharacteristic($accessoryProtocolVersion);

			$accessory->addService($accessoryProtocolInformation);
		}

		return $accessory;
	}

	/**
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function buildService(
		HomeKitTypes\ServiceType $type,
		Protocol\Accessories\Accessory $accessory,
		Documents\Channels\Channel|null $channel = null,
	): Protocol\Services\Service
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists(strval($type->value))) {
			throw new HomeKitExceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$type->value,
			));
		}

		$serviceMetadata = $metadata->offsetGet($type->value);

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new HomeKitExceptions\InvalidState('Service definition is missing required attributes');
		}

		foreach ($this->serviceFactories as $serviceFactory) {
			if (
				(
					$channel !== null
					&& $channel::getType() === $serviceFactory->getEntityClass()::getType()
				) || (
					$type === HomeKitTypes\ServiceType::ACCESSORY_INFORMATION
					&& $serviceFactory instanceof Protocol\Services\GenericFactory
				)
			) {
				return $serviceFactory->create(
					HomeKitHelpers\Protocol::hapTypeToUuid(strval($serviceMetadata->offsetGet('UUID'))),
					$type,
					$accessory,
					$channel,
					(array) $serviceMetadata->offsetGet('RequiredCharacteristics'),
					$serviceMetadata->offsetExists('OptionalCharacteristics') && $serviceMetadata->offsetGet(
						'OptionalCharacteristics',
					) instanceof Utils\ArrayHash ? (array) $serviceMetadata->offsetGet(
						'OptionalCharacteristics',
					) : [],
					$serviceMetadata->offsetExists('VirtualCharacteristics') && $serviceMetadata->offsetGet(
						'VirtualCharacteristics',
					) instanceof Utils\ArrayHash ? (array) $serviceMetadata->offsetGet(
						'VirtualCharacteristics',
					) : [],
				);
			}
		}

		throw new HomeKitExceptions\InvalidState('Service could not be created');
	}

	/**
	 * @param array<int>|null $validValues
	 *
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function buildCharacteristic(
		HomeKitTypes\ChannelPropertyIdentifier $identifier,
		Protocol\Services\Service $service,
		DevicesDocuments\Channels\Properties\Property|null $property = null,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		HomeKitTypes\CharacteristicUnit|null $unit = null,
	): Protocol\Characteristics\Characteristic
	{
		$name = str_replace(' ', '', ucwords(str_replace('_', ' ', $identifier->value)));

		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($name)) {
			throw new HomeKitExceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$name,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($name);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('UUID')
			|| !is_string($characteristicMetadata->offsetGet('UUID'))
			|| !$characteristicMetadata->offsetExists('Format')
			|| !is_string($characteristicMetadata->offsetGet('Format'))
			|| HomeKitTypes\DataType::tryFrom($characteristicMetadata->offsetGet('Format')) === null
			|| !$characteristicMetadata->offsetExists('Permissions')
			|| !$characteristicMetadata->offsetGet('Permissions') instanceof Utils\ArrayHash
		) {
			throw new HomeKitExceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		if (
			$unit === null
			&& $characteristicMetadata->offsetExists('Unit')
			&& HomeKitTypes\CharacteristicUnit::tryFrom(strval($characteristicMetadata->offsetGet('Unit'))) !== null
		) {
			$unit = HomeKitTypes\CharacteristicUnit::from(strval($characteristicMetadata->offsetGet('Unit')));
		}

		if ($minValue === null && $characteristicMetadata->offsetExists('MinValue')) {
			$minValue = floatval($characteristicMetadata->offsetGet('MinValue'));
		}

		if ($maxValue === null && $characteristicMetadata->offsetExists('MaxValue')) {
			$maxValue = floatval($characteristicMetadata->offsetGet('MaxValue'));
		}

		if ($minStep === null && $characteristicMetadata->offsetExists('MinStep')) {
			$minStep = floatval($characteristicMetadata->offsetGet('MinStep'));
		}

		if ($maxLength === null && $characteristicMetadata->offsetExists('MaximumLength')) {
			$maxLength = intval($characteristicMetadata->offsetGet('MaximumLength'));
		}

		if ($characteristicMetadata->offsetExists('ValidValues')) {
			$defaultValidValues = is_array($characteristicMetadata->offsetGet('ValidValues'))
				? array_values(
					$characteristicMetadata->offsetGet('ValidValues'),
				)
				: null;

			$validValues = $validValues !== null && $defaultValidValues !== null
				? array_values(
					array_intersect($validValues, $defaultValidValues),
				)
				: $defaultValidValues;

			if (is_array($validValues)) {
				$validValues = array_map(static fn ($item): int => intval($item), $validValues);
			}
		} else {
			$validValues = null;
		}

		if ($property !== null) {
			if (
				$property->getFormat() instanceof Formats\StringEnum
				|| $property->getFormat() instanceof Formats\CombinedEnum
			) {
				$validValues = [];

				if ($property->getFormat() instanceof Formats\StringEnum) {
					$validValues = array_map(
						static fn (string $item): int => intval($item),
						$property->getFormat()->toArray(),
					);

				} else {
					foreach ($property->getFormat()->getItems() as $item) {
						if ($item[1] instanceof Formats\CombinedEnumItem) {
							$validValues[] = intval(ValuesUtilities\Value::flattenValue($item[1]->getValue()));
						}
					}
				}
			} elseif ($property->getFormat() instanceof Formats\NumberRange) {
				$minValue = $property->getFormat()->getMin() ?? $minValue;
				$maxValue = $property->getFormat()->getMax() ?? $maxValue;
			}

			$minStep = $property->getStep() ?? $minStep;
		}

		$default = null;

		if ($characteristicMetadata->offsetExists('Default')) {
			$default = $characteristicMetadata->offsetGet('Default');
			assert(is_string($default) || is_numeric($default) || is_bool($default));
		}

		foreach ($this->characteristicsFactories as $characteristicFactory) {
			if (
				(
					$characteristicFactory->getEntityClass() !== null
					&& (
						$property !== null
						&& $property::getType() === $characteristicFactory->getEntityClass()::getType()
					)
				) || (
					$service->getChannel() === null
					&& $characteristicFactory instanceof Protocol\Characteristics\GenericFactory
				)
			) {
				return $characteristicFactory->create(
					HomeKitHelpers\Protocol::hapTypeToUuid(strval($characteristicMetadata->offsetGet('UUID'))),
					$name,
					HomeKitTypes\DataType::from($characteristicMetadata->offsetGet('Format')),
					array_map(
						static fn (string $permission): HomeKitTypes\CharacteristicPermission => HomeKitTypes\CharacteristicPermission::from(
							$permission,
						),
						(array) $characteristicMetadata->offsetGet('Permissions'),
					),
					$service,
					$property,
					$validValues,
					$maxLength,
					$minValue,
					$maxValue,
					$minStep,
					ValuesUtilities\Value::flattenValue($property?->getDefault() ?? $default),
					$unit,
				);
			}
		}

		throw new HomeKitExceptions\InvalidState('Characteristic could not be created');
	}

}
