<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas\Channels;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Thermostat channel entity schema
 *
 * @template T of Entities\Channels\Thermostat
 * @extends  HomeKitSchemas\Channels\Channel<T>
 */
final class Thermostat extends HomeKitSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value . '/channel/' . Entities\Channels\Thermostat::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Thermostat::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
