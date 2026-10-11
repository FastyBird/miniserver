<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queries\Entities;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\Connectors\Connector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 */
class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
