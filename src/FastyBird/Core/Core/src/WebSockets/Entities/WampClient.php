<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Controllers;
use Nette\Utils;

/**
 * WAMP single client connection
 */
final class WampClient extends Client implements ConnectedClient
{

	/**
	 * @throws Utils\JsonException
	 */
	public function event(Topics\Topic $topic, mixed $message): void
	{
		$this->send(Utils\Json::encode([Controllers\WampApplication::MSG_EVENT, (string) $topic, $message]));
	}

}
