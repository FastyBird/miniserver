<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Schemas\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Preset channel entity schema
 *
 * @template T of Entities\Channels\Preset
 * @extends  VirtualSchemas\Channels\Channel<T>
 */
final class Preset extends VirtualSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Addon::VIRTUAL_THERMOSTAT->value . '/channel/' . Entities\Channels\Preset::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Preset::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
