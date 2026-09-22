<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use React\Socket;

/**
 * Client connection factory
 */
class ClientFactory implements IClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): Entities\IClient
	{
		return new Entities\Client($id, $connection);
	}

}
