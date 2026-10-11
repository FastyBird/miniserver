<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queries\Configuration;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Documents\Devices\Device
 * @extends  DevicesQueries\Configuration\FindDevices<T>
 */
final class FindDevices extends DevicesQueries\Configuration\FindDevices
{

}
