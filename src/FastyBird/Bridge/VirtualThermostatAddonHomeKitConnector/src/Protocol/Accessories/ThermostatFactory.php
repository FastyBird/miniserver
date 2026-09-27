<?php declare(strict_types = 1);

/**
 * ThermostatFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           29.01.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents as VirtualThermostatAddonHomeKitConnectorDocuments;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use function assert;

/**
 * HAP thermostat accessory factory
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThermostatFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

	public function create(
		string $name,
		int|null $aid,
		HomeKitTypes\AccessoryCategory $category,
		HomeKitDocuments\Devices\Device $device,
	): Thermostat
	{
		assert($device instanceof VirtualThermostatAddonHomeKitConnectorDocuments\Devices\Thermostat);

		return new Thermostat($name, $aid, $category, $device);
	}

	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

}
