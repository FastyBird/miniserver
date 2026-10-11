<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\MotorReverse::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\MotorReverse::TYPE)]
class MotorReverse extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\MotorReverse::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::MOTOR_REVERSE->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
