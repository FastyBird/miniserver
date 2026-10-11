<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents as VirtualThermostatAddonHomeKitConnectorDocuments;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;
use function assert;

/**
 * HAP thermostat accessory factory
 */
final class ThermostatFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

	#[Override]
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

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Thermostat::class;
	}

}
