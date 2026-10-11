<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * NS Panel device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  DevicesSchemas\Devices\Device<T>
 */
abstract class Device extends DevicesSchemas\Devices\Device
{

}
