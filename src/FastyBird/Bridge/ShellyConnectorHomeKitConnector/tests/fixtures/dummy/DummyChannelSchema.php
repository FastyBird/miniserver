<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Fixtures\Dummy;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyChannelSchema extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/device/' . DummyChannelEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyChannelEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
