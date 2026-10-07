<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Server;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\ServerRuntime::create() once both sockets are listening. The modules
 * enable their SocketsBridge exchange consumers on it.
 */
final class ServerCreated extends EventDispatcher\Event
{

	public function __construct(private readonly Server\ServerRuntime $server)
	{
	}

	public function getServer(): Server\ServerRuntime
	{
		return $this->server;
	}

}
