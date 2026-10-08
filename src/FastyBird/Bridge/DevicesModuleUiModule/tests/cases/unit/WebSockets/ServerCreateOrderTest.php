<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\WebSockets;

use FastyBird\Bridge\DevicesModuleUiModule\Consumers as DevicesModuleUiModuleConsumers;
use FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Ui\Consumers as UiConsumers;
use Nette\DI;
use React\EventLoop;
use React\Socket;
use ReflectionProperty;
use SplObjectStorage;
use Symfony\Component\EventDispatcher;
use Throwable;
use function assert;
use function implode;
use const PHP_INT_MAX;

/**
 * Characterization of server create in THIS package's test container, whose order of SocketsBridge
 * enablers differs from production's (census X4, T3, T12-13).
 *
 * Here the bridge's own extension LOADS before fbCore, so when it asks whether the WAMP link
 * generator and topics storage exist, they do not yet, and its SocketsBridge is never registered
 * with the exchange consumer container -- while its ServerCreated listener still is. Before E5.6
 * (#638) its enabler was the FIRST onCreate hook here, so create() threw before anything was
 * enabled or dispatched. Since #638 the three enablers are ServerCreated listeners at the explicit
 * priorities of census T3 -- Devices -10, Ui -20, DevicesModuleUiModule -30 (#658) -- which
 * reproduce production's order everywhere (tests/cases/application/WebSocketsServerLifecycleTest).
 * So here, as X4 declares, the create event is dispatched, Devices and Ui are enabled, and the
 * bridge's own listener then throws, as its enabler always did.
 */
final class ServerCreateOrderTest extends BaseTestCase
{

	private const array BRIDGES = [
		DevicesConsumers\SocketsBridge::class => 'Devices',
		UiConsumers\SocketsBridge::class => 'Ui',
		DevicesModuleUiModuleConsumers\SocketsBridge::class => 'DevicesModuleUiModule',
	];

	/**
	 * @throws DI\MissingServiceException
	 */
	public function testOnlyTheModuleBridgesAreRegisteredAndAllStartDisabled(): void
	{
		self::assertSame('[Devices, Ui] enabled []', $this->bridges());
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testCreateEnablesDevicesAndUiThenFailsOnTheUnregisteredBridge(): void
	{
		$dispatched = [];

		$this->container->getByType(EventDispatcher\EventDispatcherInterface::class)->addListener(
			Events\ServerCreated::class,
			static function () use (&$dispatched): void {
				$dispatched[] = Events\ServerCreated::class;
			},
			PHP_INT_MAX,
		);

		$server = $this->container->getByType(Server\ServerRuntime::class);
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
		self::assertSame('[Devices, Ui] enabled [Devices, Ui]', $this->bridges());
	}

	/**
	 * The SocketsBridge consumers registered with the exchange consumer container, and the enabled
	 * ones, each in the container's order -- which is the order they consume messages in.
	 *
	 * @throws DI\MissingServiceException
	 */
	private function bridges(): string
	{
		$consumers = $this->container->getByType(ExchangeConsumers\Container::class);
		$registered = (new ReflectionProperty(ExchangeConsumers\Container::class, 'consumers'))->getValue($consumers);
		assert($registered instanceof SplObjectStorage);

		$all = [];
		$enabled = [];

		foreach ($registered as $consumer) {
			$info = $registered[$consumer];
			assert($info instanceof ExchangeConsumers\Info);

			if (!isset(self::BRIDGES[$consumer::class])) {
				continue;
			}

			$all[] = self::BRIDGES[$consumer::class];

			if ($info->isEnabled()) {
				$enabled[] = self::BRIDGES[$consumer::class];
			}
		}

		return '[' . implode(', ', $all) . '] enabled [' . implode(', ', $enabled) . ']';
	}

}
