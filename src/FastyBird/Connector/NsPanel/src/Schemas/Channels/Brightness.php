<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel brightness channel entity schema
 *
 * @extends Channel<Entities\Channels\Brightness>
 */
final class Brightness extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Brightness::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Brightness::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
