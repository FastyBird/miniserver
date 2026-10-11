<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;

/**
 * mDNS connector discovery server factory
 */
interface MdnsFactory extends ServerFactory
{

	public function create(Documents\Connectors\Connector $connector): Mdns;

}
