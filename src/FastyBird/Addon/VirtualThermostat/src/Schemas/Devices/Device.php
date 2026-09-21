<?php declare(strict_types = 1);

/**
 * VirtualThermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Schemas\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Schemas;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  VirtualSchemas\Devices\Device<T>
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends VirtualSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value . '/device/' . Entities\Devices\Device::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
