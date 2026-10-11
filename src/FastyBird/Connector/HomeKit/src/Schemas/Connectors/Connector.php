<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Schemas\Connectors;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Override;

/**
 * HomeKit connector entity schema
 *
 * @extends DevicesSchemas\Connectors\Connector<Entities\Connectors\Connector>
 */
final class Connector extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::HOMEKIT->value . '/connector/' . Entities\Connectors\Connector::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Connectors\Connector::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
