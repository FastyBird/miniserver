<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Queries\Configuration;

use FastyBird\Addon\VirtualThermostat\Documents;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;

/**
 * Find thermostat devices entities query
 *
 * @template T of Documents\Devices\Device
 * @extends  VirtualQueries\Configuration\FindDevices<T>
 */
final class FindDevices extends VirtualQueries\Configuration\FindDevices
{

}
