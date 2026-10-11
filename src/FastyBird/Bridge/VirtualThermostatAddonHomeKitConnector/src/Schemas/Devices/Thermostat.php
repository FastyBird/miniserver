<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas\Devices;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  HomeKitSchemas\Devices\Device<T>
 */
final class Thermostat extends HomeKitSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string SCHEMA_TYPE = Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value . '/device/' . Entities\Devices\Thermostat::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
