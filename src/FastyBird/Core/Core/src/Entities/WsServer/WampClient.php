<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer;

use FastyBird\Core\Controllers\WebSockets\WampApplication;
use Nette\Utils;
use Override;

/**
 * WAMP single client connection
 */
final class WampClient extends Client implements IWampClient
{

	/**
	 * {@inheritDoc}
	 *
	 * @throws Utils\JsonException
	 */
	#[Override]
	public function event(Topics\ITopic $topic, mixed $message): void
	{
		$this->send(Utils\Json::encode([WampApplication::MSG_EVENT, (string) $topic, $message]));
	}

}
