<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Schemas\Devices;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Modbus device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\Devices\Device>
 */
final class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::MODBUS->value . '/device/' . Entities\Devices\Device::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
