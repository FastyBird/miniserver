<?php declare(strict_types = 1);

/**
 * Toggle.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           03.10.24
 */

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents as ApplicationDocuments;
use function array_key_exists;
use function preg_match;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Toggle::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Toggle::TYPE)]
class Toggle extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Toggle::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function toDefinition(): array
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $this->getIdentifier(), $matches);

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
