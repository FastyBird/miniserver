<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\ColorRgb::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\ColorRgb::TYPE)]
final class ColorRgb extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\ColorRgb::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::COLOR_RGB->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
