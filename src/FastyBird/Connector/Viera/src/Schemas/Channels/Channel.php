<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Schemas\Channels;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Override;

/**
 * Viera channel entity schema
 *
 * @extends DevicesSchemas\Channels\Channel<Entities\Channels\Channel>
 */
final class Channel extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::VIERA->value . '/channel/' . Entities\Channels\Channel::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Channel::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
