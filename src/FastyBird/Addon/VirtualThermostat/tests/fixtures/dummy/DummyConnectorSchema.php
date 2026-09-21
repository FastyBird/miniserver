<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyConnectorSchema extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value . '/connector/' . DummyConnectorEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyConnectorEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
