<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Override;
use Ramsey\Uuid;

/**
 * NS panel motor calibration attribute factory
 */
final class MotorCalibrationFactory implements AttributeFactory
{

	#[Override]
	public function create(
		Uuid\UuidInterface $id,
		NsPanelTypes\Attribute $type,
		ValuesTypes\DataType $dataType,
		Protocol\Capabilities\Capability $capability,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		float|int|bool|string|null $defaultValue = null,
		string|null $unit = null,
	): MotorCalibration
	{
		return new MotorCalibration($id, $capability);
	}

	#[Override]
	public function getType(): NsPanelTypes\Attribute
	{
		return NsPanelTypes\Attribute::MOTOR_CALIBRATION;
	}

}
