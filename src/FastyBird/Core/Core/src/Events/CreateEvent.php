<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Server\WsServer as Server;
use Symfony\Contracts\EventDispatcher;

/**
 * Server start event
 */
final class CreateEvent extends EventDispatcher\Event
{

	public function __construct(private Server\Server $server)
	{
	}

	public function getServer(): Server\Server
	{
		return $this->server;
	}

}
