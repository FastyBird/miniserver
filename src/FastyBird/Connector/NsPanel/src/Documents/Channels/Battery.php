<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Battery::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Battery::TYPE)]
class Battery extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Battery::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::BATTERY->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
