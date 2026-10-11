<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queries\Entities;

use FastyBird\Connector\Modbus\Entities;
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
