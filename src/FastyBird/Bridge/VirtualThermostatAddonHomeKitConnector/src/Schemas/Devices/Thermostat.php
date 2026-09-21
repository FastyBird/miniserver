<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas\Devices;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Schemas as HomeKitSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  HomeKitSchemas\Devices\Device<T>
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends HomeKitSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value . '/device/' . Entities\Devices\Thermostat::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
