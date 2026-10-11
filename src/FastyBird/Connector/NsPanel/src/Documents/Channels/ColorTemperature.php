<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\ColorTemperature::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\ColorTemperature::TYPE)]
class ColorTemperature extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\ColorTemperature::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::COLOR_TEMPERATURE->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
