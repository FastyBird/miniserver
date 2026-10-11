<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Schemas\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Schemas as VirtualSchemas;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Thermostat device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  VirtualSchemas\Devices\Device<T>
 */
final class Device extends VirtualSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Addon::VIRTUAL_THERMOSTAT->value . '/device/' . Entities\Devices\Device::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
