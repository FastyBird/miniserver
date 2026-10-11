<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Types;

/**
 * HAP accessory factory interface
 */
interface AccessoryFactory
{

	public function create(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		Documents\Devices\Device $device,
	): Accessory;

	/**
	 * @return class-string<Entities\Devices\Device>
	 */
	public function getEntityClass(): string;

}
