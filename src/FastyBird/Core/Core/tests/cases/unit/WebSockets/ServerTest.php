<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use BadMethodCallException;
use FastyBird\Core\WebSockets\Server;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\EventLoop;
use React\Socket;
use RuntimeException;

/**
 * ServerRuntime::$onCreate/$onStart/$onStop used to fire only through SmartObject::__call.
 * These guard that Utils\Arrays::invoke() reaches every registered handler with the same
 * arguments the old magic call did.
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

		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration());

		$received = null;
		$server->onCreate[] = static function (Server\ServerRuntime $s) use (&$received): void {
			$received = $s;
		};

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

		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration());

		$receivedLoop = null;
		$receivedServer = null;
		$server->onStart[] = static function (
			EventLoop\LoopInterface $l,
			Server\ServerRuntime $s,
		) use (
			&$receivedLoop,
			&$receivedServer,
		): void {
			$receivedLoop = $l;
			$receivedServer = $s;
		};

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

		$server = new Server\ServerRuntime($handlers, $loop, new Server\Configuration());

		$receivedLoop = null;
		$receivedServer = null;
		$server->onStop[] = static function (
			EventLoop\LoopInterface $l,
			Server\ServerRuntime $s,
		) use (
			&$receivedLoop,
			&$receivedServer,
		): void {
			$receivedLoop = $l;
			$receivedServer = $s;
		};

		$server->stop();

		self::assertSame($loop, $receivedLoop);
		self::assertSame($server, $receivedServer);
	}

}
