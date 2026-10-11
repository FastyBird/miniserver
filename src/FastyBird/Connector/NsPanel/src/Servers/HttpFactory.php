<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Connector\NsPanel\Documents;

/**
 * Connector HTTP communication server factory
 */
interface HttpFactory extends ServerFactory
{

	public function create(Documents\Connectors\Connector $connector): Http;

}
