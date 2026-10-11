<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Rssi::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Rssi::TYPE)]
final class Rssi extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Rssi::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::RSSI->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
