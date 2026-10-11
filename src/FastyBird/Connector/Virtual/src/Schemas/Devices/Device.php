<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Schemas\Devices;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesSchemas\Devices\Device<T>
 */
abstract class Device extends DevicesSchemas\Devices\Device
{

}
