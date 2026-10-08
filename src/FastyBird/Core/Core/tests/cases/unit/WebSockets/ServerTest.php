<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use BadMethodCallException;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Server;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\EventLoop;
use React\Socket;
use RuntimeException;
use Symfony\Component\EventDispatcher;

/**
 * ServerRuntime's create, start and stop hooks used to fire only through SmartObject::__call, and
 * then through Utils\Arrays::invoke(). Since #638 they are the ServerCreated, ServerStarted and
 * ServerStopped events; these guard that a listener of each gets the same arguments the old
 * handlers did.
 */
final class ServerTest extends TestCase
{

	/**
	 * @throws BadMethodCallException
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function testOnCreateFiresRegisteredHandlerWithServerInstance(): void
	{
		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$handlers = $this->createMock(Server\Handlers::class);

		$dispatcher = new EventDispatcher\EventDispatcher();
		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration(), $dispatcher);

		$received = null;
		$dispatcher->addListener(
			Events\ServerCreated::class,
			static function (Events\ServerCreated $event) use (&$received): void {
				$received = $event->getServer();
			},
		);

		$socket = $this->createMock(Socket\SocketServer::class);
		$flashSocket = $this->createMock(Socket\SocketServer::class);

		$server->create($socket, $flashSocket);

		self::assertSame($server, $received);
	}

	public function testOnStartFiresRegisteredHandlerWithLoopAndServer(): void
	{
		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$loop->expects(self::once())
			->method('run');

		$handlers = $this->createMock(Server\Handlers::class);

		$dispatcher = new EventDispatcher\EventDispatcher();
		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration(), $dispatcher);

		$receivedLoop = null;
		$receivedServer = null;
		$dispatcher->addListener(
			Events\ServerStarted::class,
			static function (Events\ServerStarted $event) use (&$receivedLoop, &$receivedServer): void {
				$receivedLoop = $event->getEventLoop();
				$receivedServer = $event->getServer();
			},
		);

		$server->run();

		self::assertSame($loop, $receivedLoop);
		self::assertSame($server, $receivedServer);
	}

	public function testOnStopFiresRegisteredHandlerWithLoopAndServer(): void
	{
		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$loop->expects(self::once())
			->method('stop');

		$handlers = $this->createMock(Server\Handlers::class);

		$dispatcher = new EventDispatcher\EventDispatcher();
		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration(), $dispatcher);

		$receivedLoop = null;
		$receivedServer = null;
		$dispatcher->addListener(
			Events\ServerStopped::class,
			static function (Events\ServerStopped $event) use (&$receivedLoop, &$receivedServer): void {
				$receivedLoop = $event->getEventLoop();
				$receivedServer = $event->getServer();
			},
		);

		$server->stop();

		self::assertSame($loop, $receivedLoop);
		self::assertSame($server, $receivedServer);
	}

}
