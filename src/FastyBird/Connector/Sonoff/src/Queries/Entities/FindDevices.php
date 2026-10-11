<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queries\Entities;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesQueries\Entities\FindDevices<T>
 */
class FindDevices extends DevicesQueries\Entities\FindDevices
{

}
