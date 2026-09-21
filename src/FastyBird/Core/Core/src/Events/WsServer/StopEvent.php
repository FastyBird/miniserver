<?php declare(strict_types = 1);

/**
 * StopEvent.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.11.19
 */

namespace FastyBird\Core\Events\WsServer;

use FastyBird\Core\Server\WsServer as Server;
use React\EventLoop;
use Symfony\Contracts\EventDispatcher;

/**
 * Server stop event
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
