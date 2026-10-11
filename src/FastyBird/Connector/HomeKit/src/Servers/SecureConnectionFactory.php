<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;
use React\Socket;

/**
 * HTTP secured server connection wrapper factory
 */
interface SecureConnectionFactory
{

	public function create(
		Documents\Connectors\Connector $connector,
		string|null $sharedKey,
		Socket\ConnectionInterface $connection,
	): SecureConnection;

}
