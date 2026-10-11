<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queries\Entities;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesQueries\Entities\FindChannels<T>
 */
final class FindChannels extends DevicesQueries\Entities\FindChannels
{

}
