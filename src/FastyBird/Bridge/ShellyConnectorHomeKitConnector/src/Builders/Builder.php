<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Builders
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Builders;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Queries;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Connector\Shelly\Queries as ShellyQueries;
use FastyBird\Connector\Shelly\Types as ShellyTypes;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Formats as ToolsFormats;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Core\Tools\Utilities as ToolsUtilities;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Throwable;
use TypeError;
use ValueError;
use function array_diff;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function floatval;
use function in_array;
use function is_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function strval;

/**
 * HomeKit device builder
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Builders
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Builder
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ShellyConnectorHomeKitConnector\Logger $logger,
		private readonly Mapping\Builder $mappingBuilder,
		private readonly HomeKitHelpers\Loader $loader,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
		private readonly Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function build(
		ShellyEntities\Devices\Device $shelly,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		HomeKitTypes\AccessoryCategory $category,
		Entities\Devices\Shelly|null $accessory = null,
	): Entities\Devices\Shelly
	{
		$updated = null;

		try {
			if ($accessory === null) {
				$findAccessoryQuery = new Queries\Entities\FindShellyDevices();
				$findAccessoryQuery->forParent($shelly);

				$accessory = $this->devicesRepository->findOneBy(
					$findAccessoryQuery,
					Entities\Devices\Shelly::class,
				);
			}

			$updated = $this->createAccessory(
				$shelly,
				$homeKitConnector,
				$category,
				$accessory,
			);

			$devicesMapping = $this->loadShellyMapping($shelly);

			$createdServices = [];

			foreach ($devicesMapping as $deviceMapping) {
				if (in_array($category, $deviceMapping->getCategories(), true)) {
					$servicesMapping = $deviceMapping->findForCategory($category);

					if ($servicesMapping !== []) {
						foreach ($servicesMapping as $serviceMapping) {
							if ($this->createService($shelly, $updated, $serviceMapping)) {
								$createdServices[] = $serviceMapping->getType()->value;
							}
						}
					}
				}
			}

			if ($createdServices === []) {
				throw new Exceptions\InvalidState(
					'No services were assigned to accessory. Accessory could not be created.',
				);
			}
		} catch (Throwable $ex) {
			if ($updated !== null && $accessory === null) {
				$this->devicesManager->delete($updated);
			}

			throw $ex;
		}

		return $updated;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function createAccessory(
		ShellyEntities\Devices\Device $shelly,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		HomeKitTypes\AccessoryCategory $category,
		Entities\Devices\Shelly|null $accessory = null,
	): Entities\Devices\Shelly
	{
		try {
			if ($accessory === null) {
				$identifier = $shelly->getIdentifier();

				$findDeviceQuery = new HomeKitQueries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($shelly->getIdentifier());

				$existing = $this->devicesRepository->findOneBy(
					$findDeviceQuery,
					HomeKitEntities\Devices\Device::class,
				);

				if ($existing !== null) {
					$identifierPattern = $shelly->getIdentifier() . '-%d';
					$identifier = null;

					for ($i = 1; $i <= 100; $i++) {
						$findDeviceQuery = new HomeKitQueries\Entities\FindDevices();
						$findDeviceQuery->byIdentifier(sprintf($identifierPattern, $i));

						if (
							$this->devicesRepository->findOneBy(
								$findDeviceQuery,
								HomeKitEntities\Devices\Device::class,
							) === null
						) {
							$identifier = sprintf($identifierPattern, $i);

							break;
						}
					}
				}

				if ($identifier === null) {
					throw new Exceptions\InvalidState('Device identifier could not be calculated');
				}

				$categoryProperty = $modelProperty = $manufacturerProperty = $serialNumberProperty = null;
			} else {
				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value);

				$categoryProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$categoryProperty !== null
					&& !$categoryProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($categoryProperty);

					$categoryProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::MODEL->value);

				$modelProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if ($modelProperty !== null && !$modelProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->delete($modelProperty);

					$modelProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value);

				$manufacturerProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$manufacturerProperty !== null
					&& !$manufacturerProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($manufacturerProperty);

					$manufacturerProperty = null;
				}

				$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($accessory);
				$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value);

				$serialNumberProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

				if (
					$serialNumberProperty !== null
					&& !$serialNumberProperty instanceof DevicesEntities\Devices\Properties\Variable
				) {
					$this->devicesPropertiesManager->delete($serialNumberProperty);

					$serialNumberProperty = null;
				}
			}

			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($shelly);
			$findDevicePropertyQuery->byIdentifier(ShellyTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value);

			$shellySerialNumberProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($shelly);
			$findDevicePropertyQuery->byIdentifier(ShellyTypes\DevicePropertyIdentifier::GENERATION->value);

			$shellyGenerationProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			if ($shellyGenerationProperty === null) {
				throw new Exceptions\InvalidState('Shelly device generation info could not be loaded');
			}

			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($shelly);
			$findDevicePropertyQuery->byIdentifier(ShellyTypes\DevicePropertyIdentifier::MODEL->value);

			$shellyModelProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$devicesMapping = $this->loadShellyMapping($shelly);

			$allowedCategories = [];

			foreach ($devicesMapping as $deviceMapping) {
				$allowedCategories = array_merge($allowedCategories, $deviceMapping->getCategories());
			}

			if (!in_array($category, $allowedCategories, true)) {
				throw new Exceptions\InvalidState(
					'Provided accessory category is not supported by selected shelly device',
				);
			}

			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			if ($accessory === null) {
				$accessory = $this->devicesManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Devices\Shelly::class,
					'connector' => $homeKitConnector,
					'identifier' => $identifier,
					'parents' => [$shelly],
					'name' => $shelly->getName(),
				]));
				assert($accessory instanceof Entities\Devices\Shelly);
			}

			if ($categoryProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value,
					'name' => DevicesUtilities\Name::createName(HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value),
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => $category->value,
					'device' => $accessory,
				]));
			} else {
				// If category is changed, whole accessory have to be redefined
				if ($categoryProperty->getValue() !== $category->value) {
					foreach ($accessory->getChannels() as $channel) {
						$this->channelsManager->delete($channel);
					}
				}

				$this->devicesPropertiesManager->update($categoryProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => $category->value,
				]));
			}

			if ($modelProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MODEL->value,
					'name' => DevicesUtilities\Name::createName(HomeKitTypes\DevicePropertyIdentifier::MODEL->value),
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $shellyModelProperty?->getValue() ?? ShellyConnectorHomeKitConnector\Constants::MODEL,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($modelProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $shellyModelProperty?->getValue() ?? ShellyConnectorHomeKitConnector\Constants::MODEL,
				]));
			}

			if ($manufacturerProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
					'name' => DevicesUtilities\Name::createName(
						HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
					),
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => ShellyConnectorHomeKitConnector\Constants::MANUFACTURER,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($manufacturerProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => ShellyConnectorHomeKitConnector\Constants::MANUFACTURER,
				]));
			}

			if ($shellySerialNumberProperty !== null) {
				if ($serialNumberProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
						'name' => DevicesUtilities\Name::createName(
							HomeKitTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
						),
						'dataType' => MetadataTypes\DataType::STRING,
						'value' => $shellySerialNumberProperty->getValue(),
						'device' => $accessory,
					]));
				} else {
					$this->devicesPropertiesManager->update($serialNumberProperty, Utils\ArrayHash::from([
						'dataType' => MetadataTypes\DataType::STRING,
						'value' => $shellySerialNumberProperty->getValue(),
					]));
				}
			}

			$this->databaseHelper->commitTransaction();

			$accessory = $this->devicesRepository->find($accessory->getId());
			assert($accessory instanceof Entities\Devices\Shelly);

			$this->logger->debug(
				'Shelly device accessory was created',
				[
					'source' => MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value,
					'type' => 'builder',
					'shelly' => [
						'id' => $shelly->getId()->toString(),
					],
					'accessory' => [
						'id' => $accessory->getId()->toString(),
					],
				],
			);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('HomeKit device could not be created', $ex->getCode(), $ex);
		}

		return $accessory;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createService(
		ShellyEntities\Devices\Device $shelly,
		Entities\Devices\Shelly $accessory,
		Mapping\Services\Service $serviceMapping,
	): bool
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($serviceMapping->getType()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$serviceMapping->getType()->value,
			));
		}

		$serviceMetadata = $metadata->offsetGet($serviceMapping->getType()->value);

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		$channelIndex = $serviceMapping->getIndexStart();
		$serviceIndex = 1;

		$createdServices = [];

		do {
			$channel = null;

			if ($serviceMapping->getChannel() !== null) {
				$findChannelQuery = new ShellyQueries\Entities\FindChannels();
				$findChannelQuery->forDevice($shelly);

				$identifier = $serviceMapping->getChannel();

				if ($channelIndex !== null) {
					$identifier = sprintf($identifier, $channelIndex);
				}

				if (str_starts_with($identifier, '_')) {
					$findChannelQuery->endWithIdentifier($identifier);
				} elseif (str_ends_with($identifier, '_')) {
					$findChannelQuery->startWithIdentifier($identifier);
				} else {
					$findChannelQuery->byIdentifier($identifier);
				}

				$channel = $this->channelsRepository->findOneBy(
					$findChannelQuery,
					ShellyEntities\Channels\Channel::class,
				);

				if ($channel === null) {
					if ($serviceIndex > 1) {
						break;
					}

					throw new Exceptions\InvalidState('Shelly device channel for mapping property could not be loaded');
				}
			}

			try {
				$identifier = strtolower(
					strval(
						preg_replace(
							'/(?<!^)[A-Z]/',
							'_$0',
							$serviceMapping->getType()->value,
						),
					),
				) . '_' . $serviceIndex;

				$findServiceQuery = new Queries\Entities\FindShellyChannels();
				$findServiceQuery->forDevice($accessory);
				$findServiceQuery->byIdentifier($identifier);

				$service = $this->channelsRepository->findOneBy(
					$findServiceQuery,
					Entities\Channels\Shelly::class,
				);

				if ($service === null) {
					$service = $this->databaseHelper->transaction(
						function () use ($identifier, $accessory, $serviceMapping, $serviceIndex): Entities\Channels\Shelly {
							$channel = $this->channelsManager->create(Utils\ArrayHash::from([
								'entity' => $serviceMapping->getClass(),
								'identifier' => $identifier,
								'device' => $accessory,
								'name' => $this->translator->translate(
									'//shelly-connector-homekit-connector-bridge.base.misc.services.' . Utils\Strings::lower(
										$serviceMapping->getType()->value,
									),
								) . ($serviceMapping->isMultiple() ? ' ' . $serviceIndex : ''),
							]));
							assert($channel instanceof Entities\Channels\Shelly);

							return $channel;
						},
					);

					$this->logger->debug(
						'Shelly service for shelly connector accessory was created',
						[
							'source' => MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value,
							'type' => 'builder',
							'shelly' => [
								'id' => $shelly->getId()->toString(),
							],
							'accessory' => [
								'id' => $accessory->getId()->toString(),
							],
							'service' => [
								'id' => $service->getId()->toString(),
							],
						],
					);
				}
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					sprintf(
						'HomeKit service: %s could not be created',
						$serviceMapping->getType()->value,
					),
					$ex->getCode(),
					$ex,
				);
			}

			$requiredCharacteristics = (array) $serviceMetadata->offsetGet('RequiredCharacteristics');
			$optionalCharacteristics = $virtualCharacteristics = [];

			if (
				$serviceMetadata->offsetExists('OptionalCharacteristics')
				&& $serviceMetadata->offsetGet('OptionalCharacteristics') instanceof Utils\ArrayHash
			) {
				$optionalCharacteristics = (array) $serviceMetadata->offsetGet('OptionalCharacteristics');
			}

			if (
				$serviceMetadata->offsetExists('VirtualCharacteristics')
				&& $serviceMetadata->offsetGet('VirtualCharacteristics') instanceof Utils\ArrayHash
			) {
				$virtualCharacteristics = (array) $serviceMetadata->offsetGet('VirtualCharacteristics');
			}

			$createdCharacteristics = [];

			foreach ($serviceMapping->getCharacteristics() as $characteristicMapping) {
				if (
					!in_array($characteristicMapping->getType()->value, $requiredCharacteristics, true)
					&& !in_array($characteristicMapping->getType()->value, $optionalCharacteristics, true)
					&& !in_array($characteristicMapping->getType()->value, $virtualCharacteristics, true)
				) {
					continue;
				}

				$result = $this->createCharacteristic(
					$shelly,
					$channel,
					$channelIndex,
					$service,
					$characteristicMapping,
					!in_array($characteristicMapping->getType(), $optionalCharacteristics, true),
					!in_array($characteristicMapping->getType(), $virtualCharacteristics, true),
				);

				if ($result) {
					$createdCharacteristics[] = $characteristicMapping->getType();
				}
			}

			foreach ($serviceMapping->getCharacteristics() as $characteristicMapping) {
				if (
					$characteristicMapping->getRequire() !== []
					&& in_array($characteristicMapping->getType(), $createdCharacteristics, true)
					&& array_diff(
						array_map(
							static fn (HomeKitTypes\CharacteristicType $type): string => $type->value,
							$characteristicMapping->getRequire(),
						),
						array_map(
							static fn (HomeKitTypes\CharacteristicType $type): string => $type->value,
							$createdCharacteristics,
						),
					) !== []
				) {
					$identifier = strtolower(
						strval(
							preg_replace(
								'/(?<!^)[A-Z]/',
								'_$0',
								$characteristicMapping->getType()->value,
							),
						),
					);

					$findCharacteristic = new DevicesQueries\Entities\FindChannelProperties();
					$findCharacteristic->forChannel($service);
					$findCharacteristic->byIdentifier($identifier);

					$characteristic = $this->channelsPropertiesRepository->findOneBy($findCharacteristic);

					if ($characteristic !== null) {
						$this->channelsPropertiesManager->delete($characteristic);
					}
				}
			}

			if (
				array_diff(
					$requiredCharacteristics,
					array_map(
						static fn (HomeKitTypes\CharacteristicType $createdCharacteristic): string => $createdCharacteristic->value,
						$createdCharacteristics,
					),
				) !== []
			) {
				$this->channelsManager->delete($service);

				break;
			}

			if ($channelIndex !== null) {
				++$channelIndex;
			}

			++$serviceIndex;

			$createdServices[] = $serviceMapping->getType();
		} while ($channelIndex === null || $channelIndex < 10 || !$serviceMapping->isMultiple());

		return $createdServices !== [];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createCharacteristic(
		ShellyEntities\Devices\Device $shelly,
		ShellyEntities\Channels\Channel|null $channel,
		int|null $index,
		Entities\Channels\Shelly $service,
		Mapping\Characteristics\Characteristic $characteristicMapping,
		bool $optional = false,
		bool $virtual = false,
	): bool
	{
		$connectProperty = null;

		if ($characteristicMapping->getProperty() !== null) {
			if ($channel === null && $characteristicMapping->getChannel() === null) {
				throw new Exceptions\InvalidState(
					'Shelly device channel mapping is wrongly configured. Channel could not be loaded',
				);
			}

			if ($characteristicMapping->getChannel() !== null) {
				$findChannelQuery = new ShellyQueries\Entities\FindChannels();
				$findChannelQuery->forDevice($shelly);

				$channelIdentifier = $characteristicMapping->getChannel();

				if ($index !== null) {
					$channelIdentifier = sprintf($channelIdentifier, $index);
				}

				if (str_starts_with($channelIdentifier, '_')) {
					$findChannelQuery->endWithIdentifier($channelIdentifier);
				} elseif (str_ends_with($channelIdentifier, '_')) {
					$findChannelQuery->startWithIdentifier($channelIdentifier);
				} else {
					$findChannelQuery->byIdentifier($channelIdentifier);
				}

				$channel = $this->channelsRepository->findOneBy(
					$findChannelQuery,
					ShellyEntities\Channels\Channel::class,
				);
			}

			if ($channel === null) {
				throw new Exceptions\InvalidState('Shelly device channel for mapping property could not be loaded');
			}

			$propertyIdentifiers = is_array($characteristicMapping->getProperty())
				? $characteristicMapping->getProperty()
				: [$characteristicMapping->getProperty()];

			$connectProperty = null;

			foreach ($propertyIdentifiers as $propertyIdentifier) {
				$findPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
				$findPropertyQuery->forChannel($channel);
				$findPropertyQuery->endWithIdentifier($propertyIdentifier);

				$connectProperty = $this->channelsPropertiesRepository->findOneBy(
					$findPropertyQuery,
					DevicesEntities\Channels\Properties\Dynamic::class,
				);

				if ($connectProperty !== null) {
					break;
				}
			}

			if ($connectProperty === null && !$characteristicMapping->isNullable()) {
				return false;
			}
		}

		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($characteristicMapping->getType()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$characteristicMapping->getType()->value,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($characteristicMapping->getType()->value);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('Format')
			|| !is_string($characteristicMetadata->offsetGet('Format'))
			|| !$characteristicMetadata->offsetExists('DataType')
			|| (
				!is_string($characteristicMetadata->offsetGet('DataType'))
				&& !$characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash
			)
		) {
			throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		$value = null;

		if ($connectProperty !== null) {
			$entity = DevicesEntities\Channels\Properties\Mapped::class;

			$settable = $connectProperty->isSettable();
			$queryable = $connectProperty->isQueryable();

			$format = $characteristicMapping->getFormat() ?? $this->buildFormat($characteristicMetadata);
			$default = null;

			if ($characteristicMetadata->offsetExists('Default')) {
				$default = $characteristicMetadata->offsetGet('Default');
			}

			if ($characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash) {
				$dataTypes = array_map(
					static fn (string $type): MetadataTypes\DataType => MetadataTypes\DataType::from($type),
					(array) $characteristicMetadata->offsetGet('DataType'),
				);

				if ($dataTypes === []) {
					throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
				}
			} else {
				$dataTypes = [MetadataTypes\DataType::from($characteristicMetadata->offsetGet('DataType'))];
			}

			if (!in_array($connectProperty->getDataType(), $dataTypes, true) && $format === null) {
				throw new Exceptions\InvalidState(sprintf(
					'Provided Shelly property: %s could not be mapped to HomeKit characteristic due to invalid data type',
					$connectProperty->getIdentifier(),
				));
			}

			$dataType = $connectProperty->getDataType();

		} else {
			$entity = DevicesEntities\Channels\Properties\Dynamic::class;

			if ($characteristicMapping->getType() === HomeKitTypes\CharacteristicType::NAME) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				$value = $shelly->getName() ?? $shelly->getIdentifier();
			}

			if ($characteristicMapping->getValue() !== null) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				$value = $characteristicMapping->getValue();
			}

			$settable = $queryable = false;
			$format = $this->buildFormat($characteristicMetadata);
			$default = null;

			if ($characteristicMetadata->offsetExists('Default')) {
				$default = $characteristicMetadata->offsetGet('Default');
			}

			if (
				$characteristicMetadata->offsetExists('Permissions')
				&& $characteristicMetadata->offsetGet('Permissions') instanceof Utils\ArrayHash
			) {
				$permissions = (array) $characteristicMetadata->offsetGet('Permissions');

				$settable = in_array(HomeKitTypes\CharacteristicPermission::WRITE->value, $permissions, true);
			}

			if ($characteristicMetadata->offsetGet('DataType') instanceof Utils\ArrayHash) {
				$dataTypes = array_map(
					static fn (string $type): MetadataTypes\DataType => MetadataTypes\DataType::from($type),
					(array) $characteristicMetadata->offsetGet('DataType'),
				);

				if ($dataTypes === []) {
					throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
				}

				$dataType = $dataTypes[0];
			} else {
				$dataType = MetadataTypes\DataType::from($characteristicMetadata->offsetGet('DataType'));
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$identifier = strtolower(
				strval(
					preg_replace(
						'/(?<!^)[A-Z]/',
						'_$0',
						$characteristicMapping->getType()->value,
					),
				),
			);

			$findCharacteristic = new DevicesQueries\Entities\FindChannelProperties();
			$findCharacteristic->forChannel($service);
			$findCharacteristic->byIdentifier($identifier);

			$characteristic = $this->channelsPropertiesRepository->findOneBy($findCharacteristic);

			if (
				$characteristic !== null
				&& !$characteristic instanceof $entity
			) {
				$this->channelsPropertiesManager->delete($characteristic);

				$characteristic = null;
			}

			if ($characteristic === null) {
				$characteristic = $this->channelsPropertiesManager->create(
					Utils\ArrayHash::from(array_merge(
						[
							'entity' => $entity,
							'identifier' => $identifier,
							'channel' => $service,
							'dataType' => $dataType,
							'format' => $format,
						],
						$connectProperty !== null
							? [
								'parent' => $connectProperty,
							]
							: [],
						$entity === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
						$entity !== DevicesEntities\Channels\Properties\Mapped::class
							? [
								'default' => $default,
							]
							: [],
					)),
				);

				$this->logger->debug(
					'Characteristic for shelly service was created',
					[
						'source' => MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'shelly' => [
							'id' => $shelly->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicMapping->getType()->value,
						],
					],
				);
			} else {
				$this->channelsPropertiesManager->update(
					$characteristic,
					Utils\ArrayHash::from(array_merge(
						[
							'dataType' => $dataType,
							'format' => $format,
						],
						$connectProperty !== null
							? [
								'parent' => $connectProperty,
							]
							: [],
						$entity === DevicesEntities\Channels\Properties\Variable::class
							? [
								'value' => $value,
							]
							: [
								'settable' => $settable,
								'queryable' => $queryable,
							],
						$entity !== DevicesEntities\Channels\Properties\Mapped::class
							? [
								'default' => $default,
							]
							: [],
					)),
				);

				$this->logger->debug(
					'Characteristic for shelly service was updated',
					[
						'source' => MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'shelly' => [
							'id' => $shelly->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicMapping->getType()->value,
						],
					],
				);
			}

			$this->databaseHelper->commitTransaction();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState(
				sprintf(
					'HomeKit characteristic: %s could not be created',
					$characteristicMapping->getType()->value,
				),
				$ex->getCode(),
				$ex,
			);
		}

		return true;
	}

	/**
	 * @return ToolsFormats\StringEnum|array<int, float|null>|null
	 */
	private function buildFormat(Utils\ArrayHash $characteristicMetadata): ToolsFormats\StringEnum|array|null
	{
		$format = null;

		if (
			$characteristicMetadata->offsetExists('ValidValues')
			&& $characteristicMetadata->offsetGet('ValidValues') instanceof Utils\ArrayHash
		) {
			$format = new ToolsFormats\StringEnum(
				array_values((array) $characteristicMetadata->offsetGet('ValidValues')),
			);
		}

		if (
			$characteristicMetadata->offsetExists('MinValue')
			|| $characteristicMetadata->offsetExists('MaxValue')
		) {
			$format = [
				$characteristicMetadata->offsetExists('MinValue')
					? floatval($characteristicMetadata->offsetGet('MinValue'))
					: null,
				$characteristicMetadata->offsetExists('MaxValue')
					? floatval($characteristicMetadata->offsetGet('MaxValue'))
					: null,
			];
		}

		return $format;
	}

	/**
	 * @return array<Mapping\Accessories\Accessory>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function loadShellyMapping(ShellyEntities\Devices\Device $device): array
	{
		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(ShellyTypes\DevicePropertyIdentifier::GENERATION->value);

		$shellyGenerationProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		if ($shellyGenerationProperty === null) {
			throw new Exceptions\InvalidState('Shelly device generation info could not be loaded');
		}

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(ShellyTypes\DevicePropertyIdentifier::MODEL->value);

		$shellyModelProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		if ($shellyModelProperty === null) {
			throw new Exceptions\InvalidState('Shelly device model info could not be loaded');
		}

		if ($shellyGenerationProperty->getValue() === ShellyTypes\DeviceGeneration::GENERATION_1->value) {
			$shelliesMapping = $this->mappingBuilder->getGen1Mapping();

			return $shelliesMapping->findForModel(
				ToolsUtilities\Value::toString($shellyModelProperty->getValue(), true),
			);
		}

		if ($shellyGenerationProperty->getValue() === ShellyTypes\DeviceGeneration::GENERATION_2->value) {
			$shelliesMapping = $this->mappingBuilder->getGen2Mapping();

			return $shelliesMapping->findForModel(
				ToolsUtilities\Value::toString($shellyModelProperty->getValue(), true),
			);
		}

		throw new Exceptions\InvalidState('Shelly device mapping configuration could not be loaded');
	}

}
