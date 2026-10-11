<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Queries\Configuration;

use FastyBird\Connector\Virtual\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find device channels entities query
 *
 * @template T of Documents\Channels\Channel
 * @extends  DevicesQueries\Configuration\FindChannels<T>
 */
class FindChannels extends DevicesQueries\Configuration\FindChannels
{

}
