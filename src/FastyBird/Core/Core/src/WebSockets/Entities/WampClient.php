<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

use FastyBird\Core\WebSockets\Controllers;
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
		$this->send(Utils\Json::encode([Controllers\WampApplication::MSG_EVENT, (string) $topic, $message]));
	}

}
