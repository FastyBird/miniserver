<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Schemas\Channels;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Light bulb channel entity schema
 *
 * @template T of Entities\Channels\LightBulb
 * @extends  Channel<T>
 */
final class LightBulb extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::HOMEKIT->value . '/channel/' . Entities\Channels\LightBulb::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\LightBulb::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
