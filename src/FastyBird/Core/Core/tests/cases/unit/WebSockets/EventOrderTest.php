<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use Nette\DI;
use Nette\Http;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use React\EventLoop;
use React\Socket;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\EventDispatcher;
use Throwable;
use TypeError;
use function array_map;
use function assert;
use const PHP_INT_MAX;

/**
 * Characterization of each of the 12 WebSockets hooks as it reaches the event dispatcher: which
 * event class it dispatches, in which order, with which payload (#460 §1.3, §3.4; census T3, T4,
 * T12-15). The 13th, WampApplication::$onPush, went with the dead server-push pipeline in #635
 * (census X1-A).
 *
 * Until E5.6 (#638) every hook was a public callback array that WebSocketsExtension bridged onto
 * the dispatcher with an addSetup(); two of them -- onClientConnected and onIncomingMessage --
 * twice, to two different event classes, the second of which dropped the message. Since #638
 * ServerRuntime, Wrapper and Application dispatch census T4's events themselves, one per hook:
 * each duplicate pair became one class, dispatched once, and nothing else in these sequences
 * changed (#658). Each hook
 * is driven through the real ServerRuntime, Wrapper and WampApplication services of Core's
 * compiled container, so it is the compiled wiring that is pinned, not a hand-built object
 * graph. A listener at the highest priority records every event the moment it is dispatched.
 */
final class EventOrderTest extends BaseTestCase
{

	/**
	 * Every event class the WebSockets capability dispatches.
	 */
	private const array EVENTS = [
		Events\MessageProcessed::class,
		Events\ClientConnected::class,
		Events\ClientDisconnected::class,
		Events\ClientFailed::class,
		Events\ConnectionClosed::class,
		Events\ServerCreated::class,
		Events\ApplicationFailed::class,
		Events\MessageReceived::class,
		Events\ApplicationMessageReceived::class,
		Events\ConnectionOpened::class,
		Events\ServerStarted::class,
		Events\ServerStopped::class,
		Events\ServerFailed::class,
		Events\ServerLaunched::class,
	];

	/** @var list<object> */
	private array $dispatched = [];

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$dispatcher = $this->container->getByType(EventDispatcher\EventDispatcherInterface::class);

		foreach (self::EVENTS as $event) {
			$dispatcher->addListener($event, function (object $dispatched): void {
				$this->dispatched[] = $dispatched;
			}, PHP_INT_MAX);
		}
	}

	/**
	 * onCreate, onStart and onStop: one event each, carrying the server (and the loop).
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testTheServerHooksDispatchCreateStartAndStopWithTheServerAndItsLoop(): void
	{
		$server = $this->container->getByType(Server\ServerRuntime::class);
		$loop = (new ReflectionProperty(Server\ServerRuntime::class, 'loop'))->getValue($server);
		assert($loop instanceof EventLoop\LoopInterface);

		$socket = new Socket\SocketServer('127.0.0.1:0', [], $loop);
		$flashSocket = new Socket\SocketServer('127.0.0.1:0', [], $loop);

		$server->create($socket, $flashSocket);

		$loop->futureTick(static function () use ($loop): void {
			$loop->stop();
		});

		$server->run();
		$server->stop();

		$socket->close();
		$flashSocket->close();

		self::assertSame(
			[Events\ServerCreated::class, Events\ServerStarted::class, Events\ServerStopped::class],
			$this->classes(),
		);

		[$create, $start, $stop] = $this->dispatched;
		assert($create instanceof Events\ServerCreated);
		assert($start instanceof Events\ServerStarted);
		assert($stop instanceof Events\ServerStopped);

		self::assertSame($server, $create->getServer());
		self::assertSame($loop, $start->getEventLoop());
		self::assertSame($server, $start->getServer());
		self::assertSame($loop, $stop->getEventLoop());
		self::assertSame($server, $stop->getServer());
	}

	/**
	 * A successful upgrade: the wrapper's ClientConnected, then the application's ConnectionOpened.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAnUpgradeDispatchesClientConnectedThenOpen(): void
	{
		$protocol = $this->createMock(Encoding\RFC6455::class);
		$protocol->method('doHandshake')
			->willReturn(new Handshake\WampResponse(Handshake\WampResponse::S101_SWITCHING_PROTOCOLS));

		$client = $this->client(new Entities\WebSocket(false, false, $protocol));

		$this->wrapper()->handleMessage($client, 'the request headers are already marked received');

		self::assertSame(
			[
				Events\ClientConnected::class,
				Events\ConnectionOpened::class,
			],
			$this->classes(),
		);

		[$connected, $open] = $this->dispatched;
		assert($connected instanceof Events\ClientConnected);
		assert($open instanceof Events\ConnectionOpened);

		self::assertSame($client, $connected->getClient());
		self::assertSame($client->getRequest(), $connected->getHttpRequest());
		self::assertSame($this->application(), $open->getApplication());
		self::assertSame($client, $open->getClient());
		self::assertSame($client->getRequest(), $open->getHttpRequest());
	}

	/**
	 * A message on an established connection: the wrapper's MessageReceived, then the
	 * application's ApplicationMessageReceived from inside the protocol, then MessageProcessed.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAMessageDispatchesIncomingThenApplicationMessageThenAfter(): void
	{
		$protocol = $this->createMock(Encoding\RFC6455::class);
		$protocol->method('handleMessage')
			->willReturnCallback(
				static function (
					Entities\ConnectedClient $client,
					Controllers\Dispatcher $application,
					string $message,
				): void {
					$application->handleMessage($client, $client->getRequest(), $message);
				},
			);

		$client = $this->client(new Entities\WebSocket(true, false, $protocol));

		// a WAMP PREFIX message, which the application answers without routing anything
		$message = '[1, "e5", "http://e5.probe/"]';

		$this->wrapper()->handleMessage($client, $message);

		self::assertSame(
			[
				Events\MessageReceived::class,
				Events\ApplicationMessageReceived::class,
				Events\MessageProcessed::class,
			],
			$this->classes(),
		);

		[$incoming, $applicationMessage, $after] = $this->dispatched;
		assert($incoming instanceof Events\MessageReceived);
		assert($applicationMessage instanceof Events\ApplicationMessageReceived);
		assert($after instanceof Events\MessageProcessed);

		self::assertSame($client, $incoming->getClient());
		self::assertSame($client->getRequest(), $incoming->getHttpRequest());
		self::assertSame($message, $incoming->getMessage());
		self::assertSame($this->application(), $applicationMessage->getApplication());
		self::assertSame($client, $applicationMessage->getClient());
		self::assertSame($client->getRequest(), $applicationMessage->getHttpRequest());
		self::assertSame($message, $applicationMessage->getMessage());
		self::assertSame($client, $after->getClient());
		self::assertSame($client->getRequest(), $after->getHttpRequest());
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws WebSocketsExceptions\Storage
	 */
	public function testACloseDispatchesClientDisconnectedThenClose(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, $this->createMock(Encoding\RFC6455::class)));

		$this->wrapper()->handleClose($client);

		self::assertSame(
			[
				Events\ClientDisconnected::class,
				Events\ConnectionClosed::class,
			],
			$this->classes(),
		);

		[$disconnect, $close] = $this->dispatched;
		assert($disconnect instanceof Events\ClientDisconnected);
		assert($close instanceof Events\ConnectionClosed);

		self::assertSame($client, $disconnect->getClient());
		self::assertSame($client->getRequest(), $disconnect->getHttpRequest());
		self::assertSame($this->application(), $close->getApplication());
		self::assertSame($client, $close->getClient());
		self::assertSame($client->getRequest(), $close->getHttpRequest());
	}

	/**
	 * onClientError carries no exception; the application's onError does.
	 *
	 * @throws DI\MissingServiceException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testAnErrorDispatchesClientErrorThenApplicationError(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, $this->createMock(Encoding\RFC6455::class)));
		$exception = new RuntimeException('e5 probe');

		$this->wrapper()->handleError($client, $exception);

		self::assertSame(
			[
				Events\ClientFailed::class,
				Events\ApplicationFailed::class,
			],
			$this->classes(),
		);

		[$clientError, $error] = $this->dispatched;
		assert($clientError instanceof Events\ClientFailed);
		assert($error instanceof Events\ApplicationFailed);

		self::assertSame($client, $clientError->getClient());
		self::assertSame($client->getRequest(), $clientError->getHttpRequest());
		self::assertSame($this->application(), $error->getApplication());
		self::assertSame($client, $error->getClient());
		self::assertSame($client->getRequest(), $error->getHttpRequest());
		self::assertSame($exception, $error->getException());
	}

	/**
	 * @return list<string>
	 */
	private function classes(): array
	{
		return array_map(static fn (object $event): string => $event::class, $this->dispatched);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	private function wrapper(): Server\Wrapper
	{
		return $this->container->getByType(Server\Wrapper::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	private function application(): Controllers\WampApplication
	{
		return $this->container->getByType(Controllers\WampApplication::class);
	}

	/**
	 * A client whose HTTP headers are already received, with one request and one connection.
	 */
	private function client(Entities\WebSocket $webSocket): Entities\ConnectedClient&MockObject
	{
		$request = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')
			->willReturn(634);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($request);
		$client->method('getConnection')
			->willReturn($this->createMock(Socket\ConnectionInterface::class));
		$client->method('getParameter')
			->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $default);

		return $client;
	}

}
