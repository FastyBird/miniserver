<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queries\Entities;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\Connectors\Connector
 * @extends  DevicesQueries\Entities\FindConnectors<T>
 */
final class FindConnectors extends DevicesQueries\Entities\FindConnectors
{

}
