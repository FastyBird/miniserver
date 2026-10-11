<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Entities\Devices\Thermostat
 * @extends  HomeKitQueries\Entities\FindDevices<T>
 */
class FindThermostatDevices extends HomeKitQueries\Entities\FindDevices
{

}
