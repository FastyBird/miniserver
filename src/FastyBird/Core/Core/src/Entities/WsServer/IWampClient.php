<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\WsServer;

/**
 * WAMP single client connection interface
 */
interface IWampClient extends IClient
{

	public function event(Topics\ITopic $topic, mixed $message): void;

}
