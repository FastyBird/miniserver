<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queries\Configuration;

use FastyBird\Connector\NsPanel\Documents;
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
