<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Documents\Devices\Thermostat
 * @extends  HomeKitQueries\Configuration\FindDevices<T>
 */
class FindThermostatDevices extends HomeKitQueries\Configuration\FindDevices
{

}
