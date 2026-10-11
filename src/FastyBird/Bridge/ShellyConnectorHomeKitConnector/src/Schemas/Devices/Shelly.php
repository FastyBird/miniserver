<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Devices;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;
use FastyBird\Core\Values\Types\Sources;

/**
 * Shelly device entity schema
 *
 * @template T of Entities\Devices\Shelly
 * @extends  HomeKitSchemas\Devices\Device<T>
 */
final class Shelly extends HomeKitSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/device/' . Entities\Devices\Shelly::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Shelly::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
