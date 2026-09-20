<?php declare(strict_types = 1);

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use React\Socket;

/**
 * Client connection factory interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): Entities\IClient;

}
