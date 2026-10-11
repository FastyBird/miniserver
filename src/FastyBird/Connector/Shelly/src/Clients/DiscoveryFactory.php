<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;

/**
 * Devices discovery client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
