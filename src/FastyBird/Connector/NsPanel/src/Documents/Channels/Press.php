<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Press::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Press::TYPE)]
class Press extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Press::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::PRESS->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
