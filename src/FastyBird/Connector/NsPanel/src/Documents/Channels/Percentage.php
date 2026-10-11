<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Percentage::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Percentage::TYPE)]
class Percentage extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Percentage::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::PERCENTAGE->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
