<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Server;
use Symfony\Contracts\EventDispatcher;

/**
 * Server start event
 */
final class CreateEvent extends EventDispatcher\Event
{

	public function __construct(private Server\ServerRuntime $server)
	{
	}

	public function getServer(): Server\ServerRuntime
	{
		return $this->server;
	}

}
