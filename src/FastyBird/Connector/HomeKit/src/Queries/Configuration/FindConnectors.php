<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queries\Configuration;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Documents\Connectors\Connector
 * @extends  DevicesQueries\Configuration\FindConnectors<T>
 */
final class FindConnectors extends DevicesQueries\Configuration\FindConnectors
{

}
