<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Queries\Entities;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Viera
 * @extends  HomeKitQueries\Entities\FindChannels<T>
 */
final class FindVieraChannels extends HomeKitQueries\Entities\FindChannels
{

}
