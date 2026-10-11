<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Thermostat::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Thermostat::TYPE)]
class Thermostat extends HomeKitDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Thermostat::TYPE;
	}

}
