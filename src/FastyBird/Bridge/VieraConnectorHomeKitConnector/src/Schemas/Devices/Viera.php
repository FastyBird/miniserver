<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas\Devices;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Viera device entity schema
 *
 * @template T of Entities\Devices\Viera
 * @extends  HomeKitSchemas\Devices\Device<T>
 */
final class Viera extends HomeKitSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value . '/device/' . Entities\Devices\Viera::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Viera::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
