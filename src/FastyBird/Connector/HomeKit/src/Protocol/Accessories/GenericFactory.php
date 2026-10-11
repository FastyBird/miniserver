<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Types;

/**
 * HAP generic accessory factory
 */
class GenericFactory implements AccessoryFactory
{

	public function create(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		Documents\Devices\Device $device,
	): Generic
	{
		return new Generic($name, $aid, $category, $device);
	}

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

}
