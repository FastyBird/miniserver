<?php declare(strict_types = 1);

/**
 * Humidity.php
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
use FastyBird\Core\Documents\Application as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Humidity::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Humidity::TYPE)]
class Humidity extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Humidity::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::HUMIDITY->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
