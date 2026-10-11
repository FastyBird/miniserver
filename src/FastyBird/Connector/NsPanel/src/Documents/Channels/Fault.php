<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Fault::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Fault::TYPE)]
final class Fault extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Fault::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::FAULT->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
