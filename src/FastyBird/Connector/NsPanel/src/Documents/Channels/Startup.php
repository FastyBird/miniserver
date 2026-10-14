<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;
use function array_key_exists;
use function preg_match;

#[Documents\Mapping\Document(entity: Entities\Channels\Startup::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Startup::TYPE)]
final class Startup extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Startup::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		preg_match(Entities\Channels\Channel::CHANNEL_IDENTIFIER, $this->getIdentifier(), $matches);

		$definition = [
			'capability' => Types\Capability::STARTUP->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];

		if (array_key_exists('name', $matches)) {
			$definition['name'] = $matches['name'];
		}

		return $definition;
	}

}
