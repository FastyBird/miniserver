<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           21.11.23
 */

namespace FastyBird\Addon\VirtualThermostat\Helpers;

use FastyBird\Addon\VirtualThermostat\Documents;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Queries;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use TypeError;
use ValueError;
use function array_filter;
use function array_map;
use function assert;
use function floatval;
use function is_numeric;
use function sprintf;
use function str_starts_with;

/**
 * Thermostat helper
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Device
{

	public function __construct(
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getConfiguration(
		DevicesDocuments\Devices\Device $device,
	): DevicesDocuments\Channels\Channel
	{
		$findChannelQuery = new Queries\Configuration\FindConfigurationChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::CONFIGURATION);

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Configuration::class,
		);

		if ($channel === null) {
			throw new VirtualThermostatExceptions\InvalidState('Configuration channel is not configured');
		}

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getState(DevicesDocuments\Devices\Device $device): DevicesDocuments\Channels\Channel
	{
		$findChannelQuery = new Queries\Configuration\FindStateChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::STATE);

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\State::class,
		);

		if ($channel === null) {
			throw new VirtualThermostatExceptions\InvalidState('State channel is not configured');
		}

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getPreset(
		DevicesDocuments\Devices\Device $device,
		Types\ChannelIdentifier $preset,
	): DevicesDocuments\Channels\Channel
	{
		$findChannelQuery = new Queries\Configuration\FindPresetChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($preset);

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Preset::class,
		);

		if ($channel === null) {
			throw new VirtualThermostatExceptions\InvalidState(sprintf('Preset channel: %s is not configured', $preset->value));
		}

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getHvacMode(
		DevicesDocuments\Devices\Device $device,
	): DevicesDocuments\Channels\Properties\Dynamic|null
	{
		$channel = $this->getState($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_MODE->value);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getPresetMode(
		DevicesDocuments\Devices\Device $device,
	): DevicesDocuments\Channels\Properties\Dynamic|null
	{
		$channel = $this->getState($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::PRESET_MODE->value);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getTargetTemp(
		DevicesDocuments\Devices\Device $device,
		Types\Preset $preset,
	): DevicesDocuments\Channels\Properties\Dynamic|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::MANUAL) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_MANUAL);
		} elseif ($preset === Types\Preset::AWAY) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset === Types\Preset::ECO) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset === Types\Preset::HOME) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset === Types\Preset::COMFORT) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset === Types\Preset::SLEEP) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset === Types\Preset::ANTI_FREEZE) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} else {
			throw new VirtualThermostatExceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCoolingThresholdTemp(
		DevicesDocuments\Devices\Device $device,
		Types\Preset $preset,
	): float|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::MANUAL) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_MANUAL);
		} elseif ($preset === Types\Preset::AWAY) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset === Types\Preset::ECO) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset === Types\Preset::HOME) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset === Types\Preset::COMFORT) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset === Types\Preset::SLEEP) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset === Types\Preset::ANTI_FREEZE) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} else {
			throw new VirtualThermostatExceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHeatingThresholdTemp(
		DevicesDocuments\Devices\Device $device,
		Types\Preset $preset,
	): float|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::MANUAL) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_MANUAL);
		} elseif ($preset === Types\Preset::AWAY) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset === Types\Preset::ECO) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset === Types\Preset::HOME) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset === Types\Preset::COMFORT) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset === Types\Preset::SLEEP) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset === Types\Preset::ANTI_FREEZE) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} else {
			throw new VirtualThermostatExceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMaximumFloorTemp(DevicesDocuments\Devices\Device $device): float
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Entities\Devices\Device::MAXIMUM_SET_FLOOR_TEMPERATURE;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMinimumCycleDuration(DevicesDocuments\Devices\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MINIMUM_CYCLE_DURATION->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * Minimum temperature value to be cooler actor turned on (hysteresis low value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will start when the sensor equals or goes below 24.5
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLowTargetTempTolerance(DevicesDocuments\Devices\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * Maximum temperature value to be cooler actor turned on (hysteresis high value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will stop when the sensor equals or goes above 25.5
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHighTargetTempTolerance(DevicesDocuments\Devices\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE->value);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Channels\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @return array<int, DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function getActors(DevicesDocuments\Devices\Device $device): array
	{
		$findChannelQuery = new Queries\Configuration\FindActorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::ACTORS);

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Actors::class,
		);

		if ($channel === null) {
			return [];
		}

		$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

		return array_filter(
			$properties,
			static fn (DevicesDocuments\Channels\Properties\Property $property): bool =>
				(
					$property instanceof DevicesDocuments\Channels\Properties\Dynamic
					|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
				) && (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
					)
					|| str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
					)
				),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasHeaters(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getActors($device),
			static fn ($actor): bool => str_starts_with(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
			),
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasCoolers(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getActors($device),
			static fn ($actor): bool => str_starts_with(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
			),
		) !== [];
	}

	/**
	 * @return array<int, DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function getSensors(DevicesDocuments\Devices\Device $device): array
	{
		$findChannelQuery = new Queries\Configuration\FindSensorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::SENSORS);

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Sensors::class,
		);

		if ($channel === null) {
			return [];
		}

		$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

		return array_filter(
			$properties,
			static fn (DevicesDocuments\Channels\Properties\Property $property): bool =>
				(
					$property instanceof DevicesDocuments\Channels\Properties\Dynamic
					|| $property instanceof DevicesDocuments\Channels\Properties\Mapped
				) && (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
					)
					|| str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
					)
					|| str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::OPENING_SENSOR->value,
					)
					|| str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
					)
				),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasRoomTemperatureSensors(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
			),
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasFloorTemperatureSensors(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
			),
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasOpeningsSensors(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::OPENING_SENSOR->value,
			),
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	public function hasRoomHumiditySensors(DevicesDocuments\Devices\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
			),
		) !== [];
	}

	/**
	 * @return array<Types\HvacMode>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHvacModes(DevicesDocuments\Devices\Device $device): array
	{
		$format = $this->getHvacMode($device)?->getFormat();

		if (!$format instanceof Formats\StringEnum) {
			return [];
		}

		return array_map(
			static fn (string $item): Types\HvacMode => Types\HvacMode::from($item),
			$format->toArray(),
		);
	}

	/**
	 * @return array<Types\Preset>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPresetModes(DevicesDocuments\Devices\Device $device): array
	{
		$format = $this->getPresetMode($device)?->getFormat();

		if (!$format instanceof Formats\StringEnum) {
			return [];
		}

		return array_map(
			static fn (string $item): Types\Preset => Types\Preset::from($item),
			$format->toArray(),
		);
	}

}
