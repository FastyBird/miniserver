<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * WindowCovering channel entity schema
 *
 * @template T of Entities\Channels\WindowCovering
 * @extends  Shelly<T>
 */
final class WindowCovering extends Shelly
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\WindowCovering::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\WindowCovering::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
