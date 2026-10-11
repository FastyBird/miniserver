<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Outlet channel entity schema
 *
 * @template T of Entities\Channels\Outlet
 * @extends  Shelly<T>
 */
final class Outlet extends Shelly
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\Outlet::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Outlet::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
