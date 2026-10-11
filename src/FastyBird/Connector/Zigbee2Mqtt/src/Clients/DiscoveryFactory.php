<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use FastyBird\Connector\Zigbee2Mqtt\Documents;

/**
 * Connector sub-devices discovery client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
