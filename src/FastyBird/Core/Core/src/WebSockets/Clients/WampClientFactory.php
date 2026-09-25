<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Clients;

use FastyBird\Core\WebSockets\Entities;
use Override;
use React\Socket;

/**
 * WAMP client connection factory
 */
final class WampClientFactory implements ClientProvider
{

	#[Override]
	public function create(int $id, Socket\ConnectionInterface $connection): Entities\ConnectedClient
	{
		return new Entities\WampClient($id, $connection);
	}

}
