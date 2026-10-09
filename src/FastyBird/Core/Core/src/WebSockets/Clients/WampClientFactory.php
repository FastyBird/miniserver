<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Clients;

use FastyBird\Core\WebSockets\Entities;
use React\Socket;

/**
 * WAMP client connection factory
 */
final class WampClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): Entities\Client
	{
		return new Entities\WampClient($id, $connection);
	}

}
