<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Server;
use React\EventLoop;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\ServerRuntime::run(), before it runs the loop.
 */
final class ServerStarted extends EventDispatcher\Event
{

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Server\ServerRuntime $server,
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
