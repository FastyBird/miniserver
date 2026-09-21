<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function assert;
use function floatval;
use function is_numeric;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Configuration extends VirtualEntities\Channels\Channel
{

	public const TYPE = 'virtual-thermostat-addon-configuration';

	public function __construct(
		Entities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Addon
	{
		return MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT;
	}

	public function getDevice(): Entities\Devices\Device
	{
		assert($this->device instanceof Entities\Devices\Device);

		return $this->device;
	}

	/**
	 * Maximum allowed temperature measured with floor sensor
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMaximumFloorTemp(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Device::MAXIMUM_SET_FLOOR_TEMPERATURE;
	}

	/**
	 * Set a minimum amount of time that the actor will be turned on or off
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getMinimumCycleDuration(): float|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::MINIMUM_CYCLE_DURATION->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return null;
	}

	/**
	 * Minimum temperature value to be cooler actor turned on (hysteresis low value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will start when the sensor equals or goes below 24.5
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLowTargetTempTolerance(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Device::COLD_TOLERANCE;
	}

	/**
	 * Maximum temperature value to be cooler actor turned on (hysteresis high value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will stop when the sensor equals or goes above 25.5
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getHighTargetTempTolerance(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Device::HOT_TOLERANCE;
	}

}
