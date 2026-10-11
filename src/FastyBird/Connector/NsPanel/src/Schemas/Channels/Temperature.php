<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel temperature channel entity schema
 *
 * @extends Channel<Entities\Channels\Temperature>
 */
final class Temperature extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Temperature::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Temperature::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
