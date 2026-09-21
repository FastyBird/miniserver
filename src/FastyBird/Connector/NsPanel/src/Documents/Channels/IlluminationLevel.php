<?php declare(strict_types = 1);

/**
 * IlluminationLevel.php
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

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\IlluminationLevel::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\IlluminationLevel::TYPE)]
class IlluminationLevel extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\IlluminationLevel::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::ILLUMINATION_LEVEL->value,
			'permission' => Types\Permission::READ->value,
		];
	}

}
