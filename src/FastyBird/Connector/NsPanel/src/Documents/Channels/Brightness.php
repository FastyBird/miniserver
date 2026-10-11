<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Brightness::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Brightness::TYPE)]
final class Brightness extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Brightness::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::BRIGHTNESS->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
