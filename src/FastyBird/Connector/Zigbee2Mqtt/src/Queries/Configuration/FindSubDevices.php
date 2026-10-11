<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queries\Configuration;

use FastyBird\Connector\Zigbee2Mqtt\Documents;

/**
 * Find sub-devices entities query
 *
 * @template T of Documents\Devices\SubDevice
 * @extends  FindDevices<T>
 */
final class FindSubDevices extends FindDevices
{

}
