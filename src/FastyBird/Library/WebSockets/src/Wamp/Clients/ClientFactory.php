<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           06.03.17
 */

namespace FastyBird\Library\WebSockets\Wamp\Clients;

use FastyBird\Library\WebSockets\Clients as WebSocketsClients;
use FastyBird\Library\WebSockets\Entities as WebSocketsEntities;
use FastyBird\Library\WebSockets\Wamp\Entities;
use React\Socket;

/**
 * WAMP client connection factory
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ClientFactory implements WebSocketsClients\IClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): WebSocketsEntities\Clients\IClient
	{
		return new Entities\Clients\Client($id, $connection);
	}

}
