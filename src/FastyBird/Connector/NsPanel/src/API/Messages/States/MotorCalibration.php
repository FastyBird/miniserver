<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Motor calibration detection capability state
 */
final readonly class MotorCalibration implements State
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\MotorCalibration::class)]
		#[ObjectMapper\Modifiers\FieldName(Types\Attribute::MOTOR_CALIBRATION->value)]
		private Types\Payloads\MotorCalibration $motorCalibration,
	)
	{
	}

	#[Override]
	public function getType(): Types\Capability
	{
		return Types\Capability::MOTOR_CALIBRATION;
	}

	#[Override]
	public function getState(): array
	{
		return [
			Types\Attribute::MOTOR_CALIBRATION->value => $this->motorCalibration,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'value' => $this->motorCalibration->value,
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->{Types\Attribute::MOTOR_CALIBRATION->value} = $this->motorCalibration->value;

		return $json;
	}

}
