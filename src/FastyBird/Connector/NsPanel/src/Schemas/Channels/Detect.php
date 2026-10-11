<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel detect channel entity schema
 *
 * @extends Channel<Entities\Channels\Detect>
 */
final class Detect extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Detect::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Detect::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
