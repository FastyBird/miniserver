<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel power channel entity schema
 *
 * @extends Channel<Entities\Channels\Power>
 */
final class Power extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\Power::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Power::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
