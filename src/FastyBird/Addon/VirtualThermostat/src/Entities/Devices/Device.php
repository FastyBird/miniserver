<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_filter;
use function array_map;
use function assert;
use function sprintf;
use function str_starts_with;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Device extends VirtualEntities\Devices\Device
{

	public const TYPE = 'virtual-thermostat-addon';

	public const MINIMUM_TEMPERATURE = 7.0;

	public const MAXIMUM_ROOM_TEMPERATURE = 1_000.0;

	public const MAXIMUM_FLOOR_TEMPERATURE = 1_000.0;

	public const MAXIMUM_SET_ROOM_TEMPERATURE = 35.0;

	public const MAXIMUM_SET_FLOOR_TEMPERATURE = 30.0;

	public const TARGET_TEMPERATURE = 20.0;

	public const PRECISION = 0.1;

	public const COLD_TOLERANCE = 0.3;

	public const HOT_TOLERANCE = 0.3;

	protected string $device_type = self::TYPE;

	public function __construct(
		string $identifier,
		VirtualEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Addon
	{
		return Sources\Addon::VIRTUAL_THERMOSTAT;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getConfiguration(): Entities\Channels\Configuration
	{
		$channels = $this->channels
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::CONFIGURATION->value,
			);

		if ($channels->count() !== 1) {
			throw new VirtualThermostatExceptions\InvalidState('Configuration channel is not configured');
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Configuration);

		return $channel;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getState(): Entities\Channels\State
	{
		$channels = $this->channels
			->filter(
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::STATE->value,
			);

		if ($channels->count() !== 1) {
			throw new VirtualThermostatExceptions\InvalidState('State channel is not configured');
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\State);

		return $channel;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getPreset(Types\ChannelIdentifier $preset): Entities\Channels\Preset
	{
		$channels = $this->channels
			->filter(
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === $preset->value,
			);

		if ($channels->count() !== 1) {
			throw new VirtualThermostatExceptions\InvalidState(sprintf('Preset channel: %s is not configured', $preset->value));
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Preset);

		return $channel;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getHvacMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		return $this->getState()->getHvacMode();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getPresetMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		return $this->getState()->getPresetMode();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 */
	public function getTargetTemp(Types\Preset $preset): DevicesEntities\Channels\Properties\Dynamic|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::AWAY) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getTargetTemp();
		}

		if ($preset === Types\Preset::ECO) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getTargetTemp();
		}

		if ($preset === Types\Preset::HOME) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getTargetTemp();
		}

		if ($preset === Types\Preset::COMFORT) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getTargetTemp();
		}

		if ($preset === Types\Preset::SLEEP) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getTargetTemp();
		}

		if ($preset === Types\Preset::ANTI_FREEZE) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getTargetTemp();
		}

		return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getTargetTemp();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCoolingThresholdTemp(Types\Preset $preset): float|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::AWAY) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getCoolingThresholdTemp();
		}

		if ($preset === Types\Preset::ECO) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getCoolingThresholdTemp();
		}

		if ($preset === Types\Preset::HOME) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getCoolingThresholdTemp();
		}

		if ($preset === Types\Preset::COMFORT) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getCoolingThresholdTemp();
		}

		if ($preset === Types\Preset::SLEEP) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getCoolingThresholdTemp();
		}

		if ($preset === Types\Preset::ANTI_FREEZE) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getCoolingThresholdTemp();
		}

		return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getCoolingThresholdTemp();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHeatingThresholdTemp(Types\Preset $preset): float|null
	{
		if ($preset === Types\Preset::AUTO) {
			return null;
		}

		if ($preset === Types\Preset::AWAY) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getHeatingThresholdTemp();
		}

		if ($preset === Types\Preset::ECO) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getHeatingThresholdTemp();
		}

		if ($preset === Types\Preset::HOME) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getHeatingThresholdTemp();
		}

		if ($preset === Types\Preset::COMFORT) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getHeatingThresholdTemp();
		}

		if ($preset === Types\Preset::SLEEP) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getHeatingThresholdTemp();
		}

		if ($preset === Types\Preset::ANTI_FREEZE) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getHeatingThresholdTemp();
		}

		return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getHeatingThresholdTemp();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMaximumFloorTemp(): float
	{
		return $this->getConfiguration()->getMaximumFloorTemp();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMinimumCycleDuration(): float|null
	{
		return $this->getConfiguration()->getMinimumCycleDuration();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLowTargetTempTolerance(): float|null
	{
		return $this->getConfiguration()->getLowTargetTempTolerance();
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHighTargetTempTolerance(): float|null
	{
		return $this->getConfiguration()->getHighTargetTempTolerance();
	}

	/**
	 * @return array<int, DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped>
	 */
	public function getActors(): array
	{
		$channels = $this->channels
			->filter(
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::ACTORS->value,
			);

		if ($channels->count() !== 1) {
			return [];
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Actors);

		return array_filter(
			$channel->getActors(),
			static fn (DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property): bool =>
				str_starts_with(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
				)
				|| str_starts_with(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
				),
		);
	}

	public function hasHeaters(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($actor): bool => str_starts_with(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
			),
		) !== [];
	}

	public function hasCoolers(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($actor): bool => str_starts_with(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
			),
		) !== [];
	}

	/**
	 * @return array<int, DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped>
	 */
	public function getSensors(): array
	{
		$channels = $this->channels
			->filter(
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::SENSORS->value,
			);

		if ($channels->count() !== 1) {
			return [];
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Sensors);

		return array_filter(
			$channel->getSensors(),
			static fn (DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property): bool =>
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
				),
		);
	}

	public function hasRoomTemperatureSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
			),
		) !== [];
	}

	public function hasFloorTemperatureSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
			),
		) !== [];
	}

	public function hasOpeningsSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::OPENING_SENSOR->value,
			),
		) !== [];
	}

	public function hasRoomHumiditySensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => str_starts_with(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
			),
		) !== [];
	}

	/**
	 * @return array<Types\HvacMode>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHvacModes(): array
	{
		$channel = $this->getState();

		$format = $channel->getHvacMode()?->getFormat();

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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getPresetModes(): array
	{
		$channel = $this->getState();

		$format = $channel->getPresetMode()?->getFormat();

		if (!$format instanceof Formats\StringEnum) {
			return [];
		}

		return array_map(
			static fn (string $item): Types\Preset => Types\Preset::from($item),
			$format->toArray(),
		);
	}

}
