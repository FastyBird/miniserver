<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Server;
use React\EventLoop;
use Symfony\Contracts\EventDispatcher;

/**
 * Server start event
 */
final class StartEvent extends EventDispatcher\Event
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
