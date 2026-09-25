<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Clients;

use FastyBird\Core\WebSockets\Entities;
use React\Socket;

/**
 * Client connection factory interface
 */
interface ClientProvider
{

	public function create(int $id, Socket\ConnectionInterface $connection): Entities\ConnectedClient;

}
