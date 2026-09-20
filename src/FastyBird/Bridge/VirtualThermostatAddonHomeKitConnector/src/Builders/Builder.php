<?php declare(strict_types = 1);

/**
 * Builder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Builders
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;

use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Types as VirtualThermostatTypes;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Formats\Tools as ToolsFormats;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function floatval;
use function in_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function strtolower;
use function strval;

/**
 * HomeKit device builder
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Builders
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Builder
{

	use Nette\SmartObject;

	public function __construct(
		private readonly VirtualThermostatAddonHomeKitConnector\Logger $logger,
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
		VirtualThermostatEntities\Devices\Device $thermostat,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		Entities\Devices\Thermostat|null $accessory = null,
	): Entities\Devices\Thermostat
	{
		$updated = null;

		try {
			if ($accessory === null) {
				$findAccessoryQuery = new Queries\Entities\FindThermostatDevices();
				$findAccessoryQuery->forParent($thermostat);

				$accessory = $this->devicesRepository->findOneBy(
					$findAccessoryQuery,
					Entities\Devices\Thermostat::class,
				);
			}

			$updated = $this->createAccessory(
				$thermostat,
				$homeKitConnector,
				$accessory,
			);

			$this->createService(
				$thermostat,
				$updated,
				HomeKitTypes\ServiceType::THERMOSTAT,
			);
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
		VirtualThermostatEntities\Devices\Device $thermostat,
		HomeKitEntities\Connectors\Connector $homeKitConnector,
		Entities\Devices\Thermostat|null $accessory = null,
	): Entities\Devices\Thermostat
	{
		try {
			if ($accessory === null) {
				$identifier = $thermostat->getIdentifier();

				$findDeviceQuery = new HomeKitQueries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($thermostat->getIdentifier());

				$existing = $this->devicesRepository->findOneBy(
					$findDeviceQuery,
					HomeKitEntities\Devices\Device::class,
				);

				if ($existing !== null) {
					$identifierPattern = $thermostat->getIdentifier() . '-%d';
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

				$categoryProperty = $modelProperty = $manufacturerProperty = null;
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
			}

			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			if ($accessory === null) {
				$accessory = $this->devicesManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Devices\Thermostat::class,
					'connector' => $homeKitConnector,
					'identifier' => $identifier,
					'parents' => [$thermostat],
					'name' => $thermostat->getName(),
				]));
				assert($accessory instanceof Entities\Devices\Thermostat);
			}

			if ($categoryProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::CATEGORY->value,
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => HomeKitTypes\AccessoryCategory::THERMOSTAT->value,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($categoryProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::UCHAR,
					'value' => HomeKitTypes\AccessoryCategory::THERMOSTAT->value,
				]));
			}

			if ($modelProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MODEL->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VirtualThermostatAddonHomeKitConnector\Constants::MODEL,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($modelProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VirtualThermostatAddonHomeKitConnector\Constants::MODEL,
				]));
			}

			if ($manufacturerProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::MANUFACTURER->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VirtualThermostatAddonHomeKitConnector\Constants::MANUFACTURER,
					'device' => $accessory,
				]));
			} else {
				$this->devicesPropertiesManager->update($manufacturerProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => VirtualThermostatAddonHomeKitConnector\Constants::MANUFACTURER,
				]));
			}

			$this->databaseHelper->commitTransaction();

			$this->logger->debug(
				'Virtual thermostat accessory was created',
				[
					'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'builder',
					'thermostat' => [
						'id' => $thermostat->getId()->toString(),
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	private function createService(
		VirtualThermostatEntities\Devices\Device $thermostat,
		Entities\Devices\Thermostat $accessory,
		HomeKitTypes\ServiceType $type,
	): void
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($type->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
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
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		try {
			$identifier = strtolower(
				strval(
					preg_replace(
						'/(?<!^)[A-Z]/',
						'_$0',
						$type->value,
					),
				),
			) . '_1';

			$findChannelsQuery = new Queries\Entities\FindThermostatChannels();
			$findChannelsQuery->forDevice($accessory);
			$findChannelsQuery->byIdentifier($identifier);

			$service = $this->channelsRepository->findOneBy(
				$findChannelsQuery,
				Entities\Channels\Thermostat::class,
			);

			if ($service === null) {
				$service = $this->databaseHelper->transaction(
					function () use ($identifier, $accessory): Entities\Channels\Thermostat {
						$channel = $this->channelsManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Channels\Thermostat::class,
							'identifier' => $identifier,
							'device' => $accessory,
							'name' => $this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.misc.services.thermostat',
							),
						]));
						assert($channel instanceof Entities\Channels\Thermostat);

						return $channel;
					},
				);

				$this->logger->debug(
					'Thermostat service for virtual thermostat accessory was created',
					[
						'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'thermostat' => [
							'id' => $thermostat->getId()->toString(),
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
					$type->value,
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

		$mappedPropertiesTypes = [
			HomeKitTypes\CharacteristicType::NAME->value => null,
			HomeKitTypes\CharacteristicType::CURRENT_HEATING_COOLING_STATE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE,
			HomeKitTypes\CharacteristicType::TARGET_HEATING_COOLING_STATE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE,
			HomeKitTypes\CharacteristicType::CURRENT_TEMPERATURE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE,
			HomeKitTypes\CharacteristicType::TARGET_TEMPERATURE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE,
			HomeKitTypes\CharacteristicType::TEMPERATURE_DISPLAY_UNITS->value => null,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			HomeKitTypes\CharacteristicType::CURRENT_RELATIVE_HUMIDITY->value => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			HomeKitTypes\CharacteristicType::TARGET_RELATIVE_HUMIDITY->value => VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_HUMIDITY,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			HomeKitTypes\CharacteristicType::COOLING_THRESHOLD_TEMPERATURE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			HomeKitTypes\CharacteristicType::HEATING_THRESHOLD_TEMPERATURE->value => VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
		];

		foreach (array_merge(
			$requiredCharacteristics,
			$optionalCharacteristics,
			$virtualCharacteristics,
		) as $characteristicType) {
			if (array_key_exists($characteristicType, $mappedPropertiesTypes)) {
				$this->createCharacteristic(
					$thermostat,
					$service,
					HomeKitTypes\CharacteristicType::from($characteristicType),
					$mappedPropertiesTypes[$characteristicType] ?? null,
					!in_array($characteristicType, $requiredCharacteristics, true),
				);
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws HomeKitExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	private function createCharacteristic(
		VirtualThermostatEntities\Devices\Device $thermostat,
		Entities\Channels\Thermostat $service,
		HomeKitTypes\CharacteristicType $characteristicType,
		VirtualThermostatTypes\ChannelPropertyIdentifier|null $propertyType,
		bool $optional = false,
	): void
	{
		$connectProperty = null;

		if (
			$propertyType !== null
			&& in_array(
				$propertyType,
				[
					VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE,
					VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY,
					VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE,
					VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE,
					VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE,
					VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_HUMIDITY,
					VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
					VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
				],
				true,
			)
		) {
			$findPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();

			if (
				in_array(
					$propertyType,
					[
						VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE,
						VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_HUMIDITY,
						VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
					],
					true,
				)
			) {
				$findPropertyQuery->forChannel(
					$thermostat->getPreset(VirtualThermostatTypes\ChannelIdentifier::PRESET_MANUAL),
				);
			} else {
				$findPropertyQuery->forChannel($thermostat->getState());
			}

			$findPropertyQuery->byIdentifier($propertyType->value);

			$connectProperty = $this->channelsPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			assert(
				(
					!$optional && $connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic
				) || $optional,
			);

			if ($connectProperty === null) {
				return;
			}
		}

		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($characteristicType->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$characteristicType->value,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($characteristicType->value);

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

			$format = $this->mapPropertyFormat($connectProperty, $characteristicType);
			$default = $connectProperty->getDefault();

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

			if (!in_array($connectProperty->getDataType(), $dataTypes, true)) {
				throw new Exceptions\InvalidState(sprintf(
					'Provided thermostat property: %s could not be mapped to HomeKit characteristic',
					$connectProperty->getIdentifier(),
				));
			}

			$dataType = $connectProperty->getDataType();

		} else {
			$entity = DevicesEntities\Channels\Properties\Dynamic::class;

			if ($characteristicType === HomeKitTypes\CharacteristicType::NAME) {
				$entity = DevicesEntities\Channels\Properties\Variable::class;

				$value = $thermostat->getName() ?? $thermostat->getIdentifier();
			}

			$settable = $queryable = false;
			$format = null;
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

			if (
				$characteristicMetadata->offsetExists('ValidValues')
				&& $characteristicMetadata->offsetGet('ValidValues') instanceof Utils\ArrayHash
			) {
				$format = new ToolsFormats\StringEnum(
					array_values((array) $characteristicMetadata->offsetGet('ValidValues')),
				);
				$default = array_values((array) $characteristicMetadata->offsetGet('ValidValues'))[0];
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
						$characteristicType->value,
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
					'Characteristic for thermostat service was created',
					[
						'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'thermostat' => [
							'id' => $thermostat->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicType->value,
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
					'Characteristic for thermostat service was updated',
					[
						'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'builder',
						'thermostat' => [
							'id' => $thermostat->getId()->toString(),
						],
						'accessory' => [
							'id' => $service->getDevice()->getId()->toString(),
						],
						'service' => [
							'id' => $service->getId()->toString(),
						],
						'characteristic' => [
							'id' => $characteristic->getId()->toString(),
							'type' => $characteristicType->value,
						],
					],
				);
			}

			$this->databaseHelper->commitTransaction();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState(
				sprintf(
					'HomeKit service: %s could not be created',
					$characteristicType->value,
				),
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return array<int, int|float|array<int, int|float|string|null>|null>|null
	 *
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function mapPropertyFormat(
		DevicesEntities\Channels\Properties\Dynamic $property,
		HomeKitTypes\CharacteristicType $characteristicType,
	): array|null
	{
		if (
			$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE->value
			&& $characteristicType === HomeKitTypes\CharacteristicType::CURRENT_HEATING_COOLING_STATE
		) {
			assert($property->getFormat() instanceof ToolsFormats\StringEnum);

			$format = [];

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacState::OFF->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacState::OFF->value,
					0,
					0,
				];
			}

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacState::HEATING->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacState::HEATING->value,
					1,
					1,
				];
			}

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacState::COOLING->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacState::COOLING->value,
					2,
					2,
				];
			}

			return $format;
		} elseif (
			$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value
			&& $characteristicType === HomeKitTypes\CharacteristicType::TARGET_HEATING_COOLING_STATE
		) {
			assert($property->getFormat() instanceof ToolsFormats\StringEnum);

			$format = [];

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacMode::OFF->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacMode::OFF->value,
					0,
					0,
				];
			}

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacMode::HEAT->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacMode::HEAT->value,
					1,
					1,
				];
			}

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacMode::COOL->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacMode::COOL->value,
					2,
					2,
				];
			}

			if ($property->getFormat()->hasItem(VirtualThermostatTypes\HvacMode::AUTO->value)) {
				$format[] = [
					VirtualThermostatTypes\HvacMode::AUTO->value,
					3,
					3,
				];
			}

			return $format;
		} elseif (
			(
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::CURRENT_TEMPERATURE
			) || (
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::TARGET_TEMPERATURE
			) || (
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::CURRENT_RELATIVE_HUMIDITY
			) || (
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_HUMIDITY->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::TARGET_RELATIVE_HUMIDITY
			) || (
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::COOLING_THRESHOLD_TEMPERATURE
			) || (
				$property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value
				&& $characteristicType === HomeKitTypes\CharacteristicType::HEATING_THRESHOLD_TEMPERATURE
			)
		) {
			assert(
				$property->getFormat() instanceof ToolsFormats\NumberRange
				|| $property->getFormat() === null,
			);

			return $property->getFormat()?->toArray();
		}

		return null;
	}

}
