<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Thermostat
 * @extends  HomeKitQueries\Configuration\FindChannels<T>
 */
final class FindThermostatChannels extends HomeKitQueries\Configuration\FindChannels
{

}
