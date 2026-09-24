<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Fixtures\Dummy;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyConnectorSchema extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value . '/connector/' . DummyConnectorEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyConnectorEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
