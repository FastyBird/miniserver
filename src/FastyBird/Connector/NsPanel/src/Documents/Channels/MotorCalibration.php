<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\MotorCalibration::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\MotorCalibration::TYPE)]
class MotorCalibration extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\MotorCalibration::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::MOTOR_CALIBRATION->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
