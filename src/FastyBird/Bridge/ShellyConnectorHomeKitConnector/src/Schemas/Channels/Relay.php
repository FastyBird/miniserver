<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Relay channel entity schema
 *
 * @template T of Entities\Channels\Relay
 * @extends  Shelly<T>
 */
final class Relay extends Shelly
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\Relay::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Relay::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
