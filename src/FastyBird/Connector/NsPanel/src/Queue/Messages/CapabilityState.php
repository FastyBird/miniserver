<?php declare(strict_types = 1);

/**
 * CapabilityState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use DateTimeInterface;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Values\Utilities;
use Orisai\ObjectMapper;

/**
 * Device capability state definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CapabilityState implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Capability::class)]
		private Types\Capability $capability,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Attribute::class)]
		private Types\Attribute $attribute,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\IlluminationLevel::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorCalibration::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorControl::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Power::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Press::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Startup::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\Toggle::class),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private int|float|string|bool|Types\Payloads\MotorCalibration|Types\Payloads\Payload|null $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $identifier = null,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return $this->capability;
	}

	public function getAttribute(): Types\Attribute
	{
		return $this->attribute;
	}

	public function getValue(): int|float|string|bool|Types\Payloads\Payload|null
	{
		return $this->value;
	}

	public function getIdentifier(): string|null
	{
		return $this->identifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'capability' => $this->getCapability()->value,
			'attribute' => $this->getAttribute()->value,
			'value' => Utilities\Value::flattenValue($this->getValue()),
			'identifier' => $this->getIdentifier(),
		];
	}

}
