<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents\Application as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Thermostat::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Thermostat::TYPE)]
class Thermostat extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Thermostat::TYPE;
	}

}
