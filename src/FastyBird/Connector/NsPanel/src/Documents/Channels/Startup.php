<?php declare(strict_types = 1);

/**
 * Startup.php
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
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents as ApplicationDocuments;
use function array_key_exists;
use function preg_match;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Startup::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Startup::TYPE)]
class Startup extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Startup::TYPE;
	}

	public function toDefinition(): array
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $this->getIdentifier(), $matches);

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
