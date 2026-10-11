<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Configuration;

use FastyBird\Connector\Zigbee2Mqtt\Documents;

/**
 * Find bridge entities query
 *
 * @template T of Documents\Devices\Bridge
 * @extends  FindDevices<T>
 */
class FindBridgeDevices extends FindDevices
{

}
