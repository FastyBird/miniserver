<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queries\Entities;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find devices entities query
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesQueries\Entities\FindDevices<T>
 */
final class FindDevices extends DevicesQueries\Entities\FindDevices
{

}
