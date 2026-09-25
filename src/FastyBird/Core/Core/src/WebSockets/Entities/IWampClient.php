<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Entities;

/**
 * WAMP single client connection interface
 */
interface IWampClient extends ConnectedClient
{

	public function event(Topics\ITopic $topic, mixed $message): void;

}
