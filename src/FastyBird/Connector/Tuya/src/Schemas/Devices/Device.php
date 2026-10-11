<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Schemas\Devices;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Override;

/**
 * Tuya device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\Devices\Device>
 */
final class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::TUYA->value . '/device/' . Entities\Devices\Device::TYPE;

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
