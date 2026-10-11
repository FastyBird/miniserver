<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\MotorControl::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\MotorControl::TYPE)]
final class MotorControl extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\MotorControl::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::MOTOR_CONTROL->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
