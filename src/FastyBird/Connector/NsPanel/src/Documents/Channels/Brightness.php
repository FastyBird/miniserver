<?php declare(strict_types = 1);

/**
 * Brightness.php
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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Brightness::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Brightness::TYPE)]
class Brightness extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Brightness::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::BRIGHTNESS->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
