<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;
use function array_key_exists;
use function preg_match;

#[Documents\Mapping\Document(entity: Entities\Channels\Toggle::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Toggle::TYPE)]
final class Toggle extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Toggle::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function toDefinition(): array
	{
		preg_match(Entities\Channels\Channel::CHANNEL_IDENTIFIER, $this->getIdentifier(), $matches);

		if (!array_key_exists('capability', $matches) || !array_key_exists('name', $matches)) {
			throw new Exceptions\InvalidState('Channel identifier is invalid');
		}

		return [
			'capability' => Types\Capability::TOGGLE->value,
			'permission' => Types\Permission::READ->value,
			'name' => $matches['name'],
		];
	}

}
