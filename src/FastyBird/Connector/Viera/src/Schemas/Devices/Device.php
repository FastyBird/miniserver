<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Schemas\Devices;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;
use Override;

/**
 * Viera device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\Devices\Device>
 */
final class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::VIERA->value . '/device/' . Entities\Devices\Device::TYPE;

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
