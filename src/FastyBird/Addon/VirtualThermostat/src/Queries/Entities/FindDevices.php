<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Queries\Entities;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;

/**
 * Find thermostat devices entities query
 *
 * @template T of Entities\Devices\Device
 * @extends  VirtualQueries\Entities\FindDevices<T>
 */
class FindDevices extends VirtualQueries\Entities\FindDevices
{

}
