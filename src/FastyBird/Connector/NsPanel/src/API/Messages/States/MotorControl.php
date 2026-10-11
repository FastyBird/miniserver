<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Motor control capability state
 */
final readonly class MotorControl implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorControl::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::MOTOR_CONTROL->value)]
		private Types\Payloads\MotorControl $motorControl,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::MOTOR_CONTROL;
	}

	public function getState(): array
	{
		return [
			Types\Attribute::MOTOR_CONTROL->value => $this->motorControl,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'value' => $this->motorControl->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::MOTOR_CONTROL->value} = $this->motorControl->value;

		return $json;
	}

}
