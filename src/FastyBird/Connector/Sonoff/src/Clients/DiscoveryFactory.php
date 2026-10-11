<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;

/**
 * Devices discovery client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
