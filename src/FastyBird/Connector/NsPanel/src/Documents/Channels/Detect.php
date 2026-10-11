<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Detect::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Detect::TYPE)]
class Detect extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Detect::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::DETECT->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
