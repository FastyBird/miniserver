<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Humidity::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Humidity::TYPE)]
final class Humidity extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Humidity::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::HUMIDITY->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
