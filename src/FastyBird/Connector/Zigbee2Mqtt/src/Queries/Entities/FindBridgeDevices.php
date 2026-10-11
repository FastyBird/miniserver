<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Entities;

use FastyBird\Connector\Zigbee2Mqtt\Entities;

/**
 * Find bridge entities query
 *
 * @template T of Entities\Devices\Bridge
 * @extends  FindDevices<T>
 */
class FindBridgeDevices extends FindDevices
{

}
