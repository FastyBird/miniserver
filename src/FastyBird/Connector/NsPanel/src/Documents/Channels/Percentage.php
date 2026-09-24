<?php declare(strict_types = 1);

/**
 * Percentage.php
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

#[Documents\Mapping\Document(entity: Entities\Channels\Percentage::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Percentage::TYPE)]
class Percentage extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Percentage::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::PERCENTAGE->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
