<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Motor control attribute
 */
class MotorControl extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::MOTOR_CONTROL,
			ValuesTypes\DataType::ENUM,
			$capability,
			[
				NsPanelTypes\Payloads\MotorControl::OPEN->value,
				NsPanelTypes\Payloads\MotorControl::CLOSE->value,
				NsPanelTypes\Payloads\MotorControl::STOP->value,
				NsPanelTypes\Payloads\MotorControl::LOCK->value,
			],
		);
	}

}
