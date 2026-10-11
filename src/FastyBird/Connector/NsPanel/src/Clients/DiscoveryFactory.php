<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Documents;

/**
 * Connector sub-devices discovery client factory
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
