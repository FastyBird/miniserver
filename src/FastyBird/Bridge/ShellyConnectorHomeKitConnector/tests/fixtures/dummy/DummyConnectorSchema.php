<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Fixtures\Dummy;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyConnectorSchema extends DevicesSchemas\Connectors\Connector
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/connector/' . DummyConnectorEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyConnectorEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
