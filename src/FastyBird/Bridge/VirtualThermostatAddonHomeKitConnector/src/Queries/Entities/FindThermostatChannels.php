<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Thermostat
 * @extends  HomeKitQueries\Entities\FindChannels<T>
 */
class FindThermostatChannels extends HomeKitQueries\Entities\FindChannels
{

}
