<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Configuration;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
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
