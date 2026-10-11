<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Queries\Configuration;

use FastyBird\Connector\Virtual\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Documents\Connectors\Connector
 * @extends  DevicesQueries\Configuration\FindConnectors<T>
 */
class FindConnectors extends DevicesQueries\Configuration\FindConnectors
{

}
