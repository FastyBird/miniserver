<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Connector\NsPanel\Documents;

/**
 * Connector base communication server factory
 */
interface ServerFactory
{

	public function create(Documents\Connectors\Connector $connector): Http;

}
