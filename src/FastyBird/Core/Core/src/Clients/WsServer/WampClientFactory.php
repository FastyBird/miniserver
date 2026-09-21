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

namespace FastyBird\Core\Clients\WsServer;

use FastyBird\Core\Entities\WsServer as WebSocketsEntities;
use React\Socket;

/**
 * WAMP client connection factory
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class WampClientFactory implements IClientFactory
{

	public function create(int $id, Socket\ConnectionInterface $connection): WebSocketsEntities\IClient
	{
		return new WebSocketsEntities\WampClient($id, $connection);
	}

}
