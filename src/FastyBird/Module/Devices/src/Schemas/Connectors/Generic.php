<?php declare(strict_types = 1);

/**
 * Generic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           08.06.24
 */

namespace FastyBird\Module\Devices\Schemas\Connectors;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Schemas;

/**
 * Generic connector entity schema
 *
 * @extends Connector<Entities\Connectors\Generic>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Generic extends Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::DEVICES->value . '/connector/' . Entities\Connectors\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Connectors\Generic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
