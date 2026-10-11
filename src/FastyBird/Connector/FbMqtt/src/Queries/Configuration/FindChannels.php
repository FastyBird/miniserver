<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queries\Configuration;

use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find channels entities query
 *
 * @template T of Documents\Channels\Channel
 * @extends  DevicesQueries\Configuration\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Configuration\FindChannels
{

}
