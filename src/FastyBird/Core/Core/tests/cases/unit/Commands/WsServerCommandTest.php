<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Commands;

use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\WebSockets\Commands;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Server;
use Nette\DI;
use React\EventLoop;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;
use const PHP_INT_MAX;

/**
 * Characterization of `fb:ws-server:start` (census T4, T12-17): the command dispatches the
 * startup event BEFORE it creates the server -- whose creation dispatches the create event --
 * and only then runs the loop. That startup event is the only server start event production ever
 * dispatches: ServerRuntime::run(), which dispatches the other one, is never called by it. E5.6
 * (#638) renames both events and must keep the order.
 *
 * The command gets the container's ServerRuntime and dispatcher, a socket on an ephemeral
 * loopback port and an event loop that returns at once.
 */
final class WsServerCommandTest extends BaseTestCase
{

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheStartupEventIsDispatchedBeforeTheServerIsCreated(): void
	{
		$steps = [];

		$dispatcher = $this->container->getByType(EventDispatcher\EventDispatcherInterface::class);

		foreach ([Events\ServerLaunched::class, Events\ServerCreated::class] as $event) {
			$dispatcher->addListener($event, static function (object $dispatched) use (&$steps): void {
				$steps[] = 'dispatched ' . $dispatched::class;
			}, PHP_INT_MAX);
		}

		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$loop->method('run')
			->willReturnCallback(static function () use (&$steps): void {
				$steps[] = 'loop run';
			});

		$server = $this->container->getByType(Server\ServerRuntime::class);

		$command = new Commands\WsServer(
			new Server\Configuration(0, '127.0.0.1'),
			$server,
			$loop,
			[],
			$dispatcher,
		);

		$result = $command->run(new ArrayInput([]), new NullOutput());

		// create() bound the server's flash-policy socket on the container's event loop, which runs
		// itself when PHP shuts down unless it has run before: run it once, for one tick
		$serverLoop = (new ReflectionProperty(Server\ServerRuntime::class, 'loop'))->getValue($server);
		assert($serverLoop instanceof EventLoop\LoopInterface);

		$serverLoop->futureTick(static function () use ($serverLoop): void {
			$serverLoop->stop();
		});
		$serverLoop->run();

		self::assertSame(Commands\WsServer::SUCCESS, $result);
		self::assertSame(
			[
				'dispatched ' . Events\ServerLaunched::class,
				'dispatched ' . Events\ServerCreated::class,
				'loop run',
			],
			$steps,
		);
	}

}
