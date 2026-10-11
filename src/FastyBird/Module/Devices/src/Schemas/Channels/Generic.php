<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Schemas\Channels;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;

/**
 * Generic channel entity schema
 *
 * @extends Channel<Entities\Channels\Generic>
 */
final class Generic extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::DEVICES->value . '/channel/' . Entities\Channels\Generic::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\Generic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
