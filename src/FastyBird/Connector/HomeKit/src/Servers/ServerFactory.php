<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;

/**
 * Base server factory
 */
interface ServerFactory
{

	public function create(Documents\Connectors\Connector $connector): Server;

}
