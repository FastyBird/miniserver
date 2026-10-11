<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queries\Entities;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find device channels entities query
 *
 * @template T of Entities\Channels\Channel
 * @extends  DevicesQueries\Entities\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Entities\FindChannels
{

}
