<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Queries\Configuration;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Viera
 * @extends  HomeKitQueries\Configuration\FindChannels<T>
 */
final class FindVieraChannels extends HomeKitQueries\Configuration\FindChannels
{

}
