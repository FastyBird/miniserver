<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Clients;

use FastyBird\Connector\Viera\Documents;

/**
 * Devices discovery client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
