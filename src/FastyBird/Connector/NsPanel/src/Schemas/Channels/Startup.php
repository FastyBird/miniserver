<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel startup channel entity schema
 *
 * @extends Channel<Entities\Channels\Startup>
 */
final class Startup extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Startup::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Startup::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
