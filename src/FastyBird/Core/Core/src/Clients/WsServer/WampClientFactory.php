<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as WebSocketsEntities;
use React\Socket;

/**
 * WAMP client connection factory
 */
class WampClientFactory implements IClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): WebSocketsEntities\IClient
	{
		return new WebSocketsEntities\WampClient($id, $connection);
	}

}
