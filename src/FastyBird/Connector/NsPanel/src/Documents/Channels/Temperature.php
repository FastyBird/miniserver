<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Temperature::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Temperature::TYPE)]
final class Temperature extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Temperature::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::TEMPERATURE->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
