<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;
use React\Socket;

/**
 * HTTP secured server wrapper factory
 */
interface SecureServerFactory
{

	public function create(
		Documents\Connectors\Connector $connector,
		Socket\ServerInterface $server,
		string|null $sharedKey = null,
	): SecureServer;

}
