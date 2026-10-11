<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\IlluminationLevel::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\IlluminationLevel::TYPE)]
class IlluminationLevel extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\IlluminationLevel::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::ILLUMINATION_LEVEL->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
