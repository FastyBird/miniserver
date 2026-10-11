<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * Input button type channel entity schema
 *
 * @template T of Entities\Channels\InputSource
 * @extends  Viera<T>
 */
final class InputSource extends Viera
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\InputSource::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\InputSource::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
