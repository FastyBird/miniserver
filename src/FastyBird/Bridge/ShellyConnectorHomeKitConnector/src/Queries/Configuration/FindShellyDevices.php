<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Documents\Devices\Shelly
 * @extends  HomeKitQueries\Configuration\FindDevices<T>
 */
class FindShellyDevices extends HomeKitQueries\Configuration\FindDevices
{

}
