<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\MotorCalibration::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\MotorCalibration::TYPE)]
final class MotorCalibration extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\MotorCalibration::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::MOTOR_CALIBRATION->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
