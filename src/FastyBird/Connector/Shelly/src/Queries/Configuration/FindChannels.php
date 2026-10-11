<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queries\Configuration;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Channel
 * @extends  DevicesQueries\Configuration\FindChannels<T>
 */
final class FindChannels extends DevicesQueries\Configuration\FindChannels
{

}
