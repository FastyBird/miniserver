<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device devices entities query
 *
 * @template T of Entities\Devices\Viera
 * @extends  HomeKitQueries\Entities\FindDevices<T>
 */
final class FindVieraDevices extends HomeKitQueries\Entities\FindDevices
{

}
