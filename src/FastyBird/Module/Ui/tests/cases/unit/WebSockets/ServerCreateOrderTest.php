<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\WebSockets;

use Error;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Module\Ui\Consumers;
use FastyBird\Module\Ui\Tests\Cases\Unit\BaseTestCase;
use Nette\DI;
use React\EventLoop;
use React\Socket;
use ReflectionProperty;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;
use const PHP_INT_MAX;

/**
 * Server create in THIS package's test containers, the base one and each per-test overlay (census
 * X4, T3, T12-13). The module's extension loads before fbCore, so when it asks whether the WAMP
 * link generator and topics storage exist, they do not yet, and its SocketsBridge is never
 * registered with the exchange consumer container -- while its ServerCreated listener, tagged
 * WebSocketsExtension::SERVER_CREATED_LISTENER_TAG at -20 (#658), still is. So create()
 * dispatches the create event and the listener throws, exactly as the module's onCreate enabler
 * threw before E5.6 (#638). Nothing fixes that here: it is pinned, not changed (#658).
 */
final class ServerCreateOrderTest extends BaseTestCase
{

	/**
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Throwable
	 */
	public function testCreateFailsOnTheUnregisteredBridge(): void
	{
		$this->assertCreateFailsOnTheUnregisteredBridge();
	}

	/**
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Throwable
	 */
	public function testCreateFailsOnTheUnregisteredBridgeWithThePrefixedRoutesOverlay(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/../Router/prefixedRoutes.neon');

		$this->assertCreateFailsOnTheUnregisteredBridge();
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Throwable
	 */
	private function assertCreateFailsOnTheUnregisteredBridge(): void
	{
		$container = $this->getContainer();

		self::assertSame([], $container->findByType(Consumers\SocketsBridge::class));

		$dispatched = [];

		$container->getByType(EventDispatcher\EventDispatcherInterface::class)->addListener(
			Events\ServerCreated::class,
			static function () use (&$dispatched): void {
				$dispatched[] = Events\ServerCreated::class;
			},
			PHP_INT_MAX,
		);

		$server = $container->getByType(Server\ServerRuntime::class);
		$loop = (new ReflectionProperty(Server\ServerRuntime::class, 'loop'))->getValue($server);
		assert($loop instanceof EventLoop\LoopInterface);

		$socket = new Socket\SocketServer('127.0.0.1:0', [], $loop);
		$flashSocket = new Socket\SocketServer('127.0.0.1:0', [], $loop);

		$thrown = null;

		try {
			$server->create($socket, $flashSocket);
		} catch (Exceptions\InvalidArgument $ex) {
			$thrown = $ex->getMessage();
		} finally {
			// the sockets live on the server's event loop, which runs itself at shutdown
			$socket->close();
			$flashSocket->close();
		}

		self::assertSame('Provided consumer is not registered in container and can not be enabled', $thrown);
		self::assertSame([Events\ServerCreated::class], $dispatched);
	}

}
