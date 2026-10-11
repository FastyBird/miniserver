<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Shelly
 * @extends  HomeKitQueries\Entities\FindChannels<T>
 */
class FindShellyChannels extends HomeKitQueries\Entities\FindChannels
{

}
