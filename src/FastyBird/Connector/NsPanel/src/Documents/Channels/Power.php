<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Power::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Power::TYPE)]
final class Power extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Power::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::POWER->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
