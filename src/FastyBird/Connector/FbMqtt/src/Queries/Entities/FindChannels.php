<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queries\Entities;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find channels entities query
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesQueries\Entities\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Entities\FindChannels
{

}
