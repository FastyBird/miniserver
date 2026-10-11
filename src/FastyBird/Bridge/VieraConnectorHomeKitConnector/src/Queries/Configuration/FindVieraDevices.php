<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Documents\Devices\Viera
 * @extends  HomeKitQueries\Configuration\FindDevices<T>
 */
final class FindVieraDevices extends HomeKitQueries\Configuration\FindDevices
{

}
