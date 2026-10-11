<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Detect::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Detect::TYPE)]
final class Detect extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Detect::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::DETECT->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
