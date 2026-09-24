<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\Schemas\Connectors;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Shelly connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\Connectors\Connector>
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Connector::SHELLY->value . '/connector/' . Entities\Connectors\Connector::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Connectors\Connector::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
