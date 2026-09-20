<?php declare(strict_types = 1);

/**
 * IClient.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           06.03.17
 */

namespace FastyBird\Core\Entities\WsServer;

use FastyBird\Core\Controllers\WebSockets\WampApplication;
use Nette\Utils;

/**
 * WAMP single client connection
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class WampClient extends Client implements IWampClient
{

	/**
	 * {@inheritDoc}
	 *
	 * @throws Utils\JsonException
	 */
	public function event(Topics\ITopic $topic, mixed $message): void
	{
		$this->send(Utils\Json::encode([WampApplication::MSG_EVENT, (string) $topic, $message]));
	}

}
