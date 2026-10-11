<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * Color lightbulb channel entity schema
 *
 * @template T of Entities\Channels\Lightbulb
 * @extends  Shelly<T>
 */
final class Lightbulb extends Shelly
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\Lightbulb::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Lightbulb::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
