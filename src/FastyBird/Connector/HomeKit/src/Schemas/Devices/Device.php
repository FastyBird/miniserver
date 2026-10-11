<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Schemas\Devices;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Override;

/**
 * HomeKit connector entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesSchemas\Devices\Device<T>
 */
class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::HOMEKIT->value . '/device/' . Entities\Devices\Device::TYPE;

	#[Override]
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
