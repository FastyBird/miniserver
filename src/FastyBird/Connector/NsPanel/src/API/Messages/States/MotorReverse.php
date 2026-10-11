<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Motor reverse rotation capability state
 */
final readonly class MotorReverse implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::MOTOR_REVERSE->value)]
		private bool $motorReverse,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::MOTOR_REVERSE;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::MOTOR_REVERSE->value => $this->motorReverse,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->motorReverse,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::MOTOR_REVERSE->value} = $this->motorReverse;

		return $json;
	}

}
