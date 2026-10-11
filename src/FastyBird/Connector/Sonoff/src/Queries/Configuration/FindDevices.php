<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queries\Configuration;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Documents\Devices\Device
 * @extends  DevicesQueries\Configuration\FindDevices<T>
 */
class FindDevices extends DevicesQueries\Configuration\FindDevices
{

}
