<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Thermostat::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Thermostat::TYPE)]
class Thermostat extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Thermostat::TYPE;
	}

}
