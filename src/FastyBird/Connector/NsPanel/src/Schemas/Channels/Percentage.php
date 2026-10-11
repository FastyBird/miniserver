<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel percentage channel entity schema
 *
 * @extends Channel<Entities\Channels\Percentage>
 */
final class Percentage extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Percentage::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Percentage::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
