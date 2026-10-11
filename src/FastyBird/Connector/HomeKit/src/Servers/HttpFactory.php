<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;

/**
 * HTTP connector communication server factory
 */
interface HttpFactory extends ServerFactory
{

	public function create(Documents\Connectors\Connector $connector): Http;

}
