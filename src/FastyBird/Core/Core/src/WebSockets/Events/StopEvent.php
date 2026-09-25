<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Server;
use React\EventLoop;
use Symfony\Contracts\EventDispatcher;

/**
 * Server stop event
 */
final class StopEvent extends EventDispatcher\Event
{

	public function __construct(
		private EventLoop\LoopInterface $eventLoop,
		private Server\ServerRuntime $server,
	)
	{
	}

	public function getEventLoop(): EventLoop\LoopInterface
	{
		return $this->eventLoop;
	}

	public function getServer(): Server\ServerRuntime
	{
		return $this->server;
	}

}
