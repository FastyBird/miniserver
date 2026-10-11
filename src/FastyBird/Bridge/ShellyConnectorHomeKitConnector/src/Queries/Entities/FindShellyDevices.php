<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Entities\Devices\Shelly
 * @extends  HomeKitQueries\Entities\FindDevices<T>
 */
class FindShellyDevices extends HomeKitQueries\Entities\FindDevices
{

}
