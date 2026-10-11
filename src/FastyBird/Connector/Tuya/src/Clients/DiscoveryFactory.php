<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Documents;

/**
 * Local devices client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
