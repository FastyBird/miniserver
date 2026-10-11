<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Thermostat::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Thermostat::TYPE)]
final class Thermostat extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Thermostat::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::THERMOSTAT->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
