<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Shelly
 * @extends  HomeKitQueries\Configuration\FindChannels<T>
 */
class FindShellyChannels extends HomeKitQueries\Configuration\FindChannels
{

}
