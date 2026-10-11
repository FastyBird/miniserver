<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Schemas\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Generic channel entity schema
 *
 * @template T of Entities\Channels\Generic
 * @extends  Channel<T>
 */
final class Generic extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::HOMEKIT->value . '/channel/' . Entities\Channels\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Generic::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
