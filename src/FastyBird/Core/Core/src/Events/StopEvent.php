<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Server\WsServer as Server;
use React\EventLoop;
use Symfony\Contracts\EventDispatcher;

/**
 * Server stop event
 */
final class StopEvent extends EventDispatcher\Event
{

	public function __construct(
		private EventLoop\LoopInterface $eventLoop,
		private Server\Server $server,
	)
	{
	}

	public function getEventLoop(): EventLoop\LoopInterface
	{
		return $this->eventLoop;
	}

	public function getServer(): Server\Server
	{
		return $this->server;
	}

}
