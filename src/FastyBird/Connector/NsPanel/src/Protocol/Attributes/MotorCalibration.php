<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Motor calibration attribute
 */
final class MotorCalibration extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::MOTOR_CALIBRATION,
			ValuesTypes\DataType::ENUM,
			$capability,
			[
				NsPanelTypes\Payloads\MotorCalibration::NORMAL->value,
				NsPanelTypes\Payloads\MotorCalibration::CALIBRATION->value,
			],
		);
	}

}
