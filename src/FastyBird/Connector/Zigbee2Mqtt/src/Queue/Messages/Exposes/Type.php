<?php declare(strict_types = 1);

/**
 * Type.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;
use function sprintf;

/**
 * Device expose type configuration message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Type implements Zigbee2Mqtt\Queue\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $name = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $label = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $property = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $access = 0,
	)
	{
	}

	abstract public function getType(): Zigbee2MqttTypes\ExposeType;

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDataType(): ValuesTypes\DataType
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return ValuesTypes\DataType::UNKNOWN;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getName(): string|null
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return $this->name !== Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE ? $this->name : null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLabel(): string|null
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return $this->label !== Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE ? $this->label : null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getProperty(): string
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		if ($this->property === Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Property is wrongly configured for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return $this->property;
	}

	/**
	 * @return array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null
	 *
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFormat(): array|null
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getUnit(): string|null
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getAccess(): int
	{
		if (
			$this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Accessing to property which is not allowed for given expose type: %s',
					$this->getType()->value,
				),
			);
		}

		return $this->access;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function isSettable(): bool
	{
		return ($this->getAccess() & 0b010) !== 0;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function isQueryable(): bool
	{
		return ($this->getAccess() & 0b100) !== 0;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return $this instanceof ClimateType
			|| $this instanceof CoverType
			|| $this instanceof FanType
			|| $this instanceof LightType
			|| $this instanceof LockType
			|| $this instanceof SwitchType
		 ? [
			 'type' => $this->getType()->value,
		 ] : [
			 'type' => $this->getType()->value,
			 'name' => $this->getName(),
			 'label' => $this->getLabel(),
			 'property' => $this->getProperty(),
			 'access' => $this->getAccess(),
		 ];
	}

}
