<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use Error;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyWebSocketsController;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Entities\PushMessages;
use FastyBird\Core\WebSockets\Entities\Topics;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Wamp;
use Nette\DI;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop;
use React\Socket;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\EventDispatcher;
use Throwable;
use TypeError;
use function array_map;
use function assert;
use const PHP_INT_MAX;

/**
 * Characterization of each of the 13 WebSockets hooks as it reaches the event dispatcher today:
 * which event class or classes it dispatches, in which order, with which payload (#460 §1.3,
 * §3.4; census T3, T4, T12-15).
 *
 * Every hook is a public callback array that WebSocketsExtension::beforeCompile() bridges onto
 * the dispatcher with an addSetup(); two of them -- onClientConnected and onIncomingMessage --
 * twice, to two different event classes, and the second onIncomingMessage bridge drops the
 * message, because IncomingMessage takes only the client and the request. E5.6 (#638) replaces
 * the arrays with direct PSR-14 dispatch and has to reproduce exactly these sequences. Each hook
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
		Events\AfterIncommingMessageEvent::class,
		Events\ClientConnectEvent::class,
		Events\ClientConnected::class,
		Events\ClientDisconnectEvent::class,
		Events\ClientErrorEvent::class,
		Events\CloseEvent::class,
		Events\CreateEvent::class,
		Events\ErrorEvent::class,
		Events\IncomingMessage::class,
		Events\IncommingMessageEvent::class,
		Events\MessageEvent::class,
		Events\OpenEvent::class,
		Events\PushEvent::class,
		Events\StartEvent::class,
		Events\StopEvent::class,
		Events\WsServerError::class,
		Events\WsServerStartup::class,
	];

	/** @var list<object> */
	private array $dispatched = [];

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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
			[Events\CreateEvent::class, Events\StartEvent::class, Events\StopEvent::class],
			$this->classes(),
		);

		[$create, $start, $stop] = $this->dispatched;
		assert($create instanceof Events\CreateEvent);
		assert($start instanceof Events\StartEvent);
		assert($stop instanceof Events\StopEvent);

		self::assertSame($server, $create->getServer());
		self::assertSame($loop, $start->getEventLoop());
		self::assertSame($server, $start->getServer());
		self::assertSame($loop, $stop->getEventLoop());
		self::assertSame($server, $stop->getServer());
	}

	/**
	 * A successful upgrade: the wrapper's onClientConnected, twice -- the Core bridge first, then
	 * the WS server's own -- then the application's onOpen.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAnUpgradeDispatchesClientConnectedTwiceThenOpen(): void
	{
		$protocol = $this->createMock(Encoding\IProtocol::class);
		$protocol->method('doHandshake')
			->willReturn(new Handshake\WampResponse(Handshake\IResponse::S101_SWITCHING_PROTOCOLS));

		$client = $this->client(new Entities\WebSocket(false, false, $protocol));

		$this->wrapper()->handleMessage($client, 'the request headers are already marked received');

		self::assertSame(
			[
				Events\ClientConnectEvent::class,
				Events\ClientConnected::class,
				Events\OpenEvent::class,
			],
			$this->classes(),
		);

		[$connect, $connected, $open] = $this->dispatched;
		assert($connect instanceof Events\ClientConnectEvent);
		assert($connected instanceof Events\ClientConnected);
		assert($open instanceof Events\OpenEvent);

		self::assertSame($client, $connect->getClient());
		self::assertSame($client->getRequest(), $connect->getHttpRequest());
		self::assertSame($client, $connected->getClient());
		self::assertSame($client->getRequest(), $connected->getHttpRequest());
		self::assertSame($this->application(), $open->getApplication());
		self::assertSame($client, $open->getClient());
		self::assertSame($client->getRequest(), $open->getHttpRequest());
	}

	/**
	 * A message on an established connection: onIncomingMessage twice -- the second event
	 * without the message, which IncomingMessage has no place for -- then the application's
	 * onMessage from inside the protocol, then onAfterIncomingMessage.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testAMessageDispatchesIncomingTwiceThenApplicationMessageThenAfter(): void
	{
		$protocol = $this->createMock(Encoding\IProtocol::class);
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
				Events\IncommingMessageEvent::class,
				Events\IncomingMessage::class,
				Events\MessageEvent::class,
				Events\AfterIncommingMessageEvent::class,
			],
			$this->classes(),
		);

		[$incoming, $incomingWithoutMessage, $applicationMessage, $after] = $this->dispatched;
		assert($incoming instanceof Events\IncommingMessageEvent);
		assert($incomingWithoutMessage instanceof Events\IncomingMessage);
		assert($applicationMessage instanceof Events\MessageEvent);
		assert($after instanceof Events\AfterIncommingMessageEvent);

		self::assertSame($client, $incoming->getClient());
		self::assertSame($client->getRequest(), $incoming->getHttpRequest());
		self::assertSame($message, $incoming->getMessage());
		self::assertSame($client, $incomingWithoutMessage->getClient());
		self::assertSame($client->getRequest(), $incomingWithoutMessage->getHttpRequest());
		self::assertFalse((new ReflectionClass(Events\IncomingMessage::class))->hasMethod('getMessage'));
		self::assertSame($this->application(), $applicationMessage->getApplication());
		self::assertSame($client, $applicationMessage->getClient());
		self::assertSame($client->getRequest(), $applicationMessage->getHttpRequest());
		self::assertSame($message, $applicationMessage->getMessage());
		self::assertSame($client, $after->getClient());
		self::assertSame($client->getRequest(), $after->getHttpRequest());
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testACloseDispatchesClientDisconnectedThenClose(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, $this->createMock(Encoding\IProtocol::class)));

		$this->wrapper()->handleClose($client);

		self::assertSame(
			[
				Events\ClientDisconnectEvent::class,
				Events\CloseEvent::class,
			],
			$this->classes(),
		);

		[$disconnect, $close] = $this->dispatched;
		assert($disconnect instanceof Events\ClientDisconnectEvent);
		assert($close instanceof Events\CloseEvent);

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
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testAnErrorDispatchesClientErrorThenApplicationError(): void
	{
		$client = $this->client(new Entities\WebSocket(true, false, $this->createMock(Encoding\IProtocol::class)));
		$exception = new RuntimeException('e5 probe');

		$this->wrapper()->handleError($client, $exception);

		self::assertSame(
			[
				Events\ClientErrorEvent::class,
				Events\ErrorEvent::class,
			],
			$this->classes(),
		);

		[$clientError, $error] = $this->dispatched;
		assert($clientError instanceof Events\ClientErrorEvent);
		assert($error instanceof Events\ErrorEvent);

		self::assertSame($client, $clientError->getClient());
		self::assertSame($client->getRequest(), $clientError->getHttpRequest());
		self::assertSame($this->application(), $error->getApplication());
		self::assertSame($client, $error->getClient());
		self::assertSame($client->getRequest(), $error->getHttpRequest());
		self::assertSame($exception, $error->getException());
	}

	/**
	 * A push is dispatched once, after its controller ran, with the message, the provider and
	 * the topic -- and not at all when no route takes it, because the application logs the
	 * failure and returns before the push hook.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Throwable
	 */
	public function testAPushIsDispatchedOnlyAfterItsControllerRan(): void
	{
		$router = $this->container->getByType(Wamp\WampRouter::class);
		assert($router instanceof Wamp\RouteList);
		$router[] = new Wamp\WampRoute('/e5/probe', 'Probe:WebSockets:');

		$controllerFactory = $this->container->getByType(Controllers\IControllerFactory::class);
		assert($controllerFactory instanceof Controllers\ControllerFactory);
		$controllerFactory->setMapping([
			'Probe' => ['FastyBird\Core\Tests\Fixtures\Dummy', '*', 'Dummy*Controller'],
		]);

		self::assertSame(
			DummyWebSocketsController::class,
			$controllerFactory->formatControllerClass('Probe:WebSockets'),
		);

		$this->application()->handlePush($this->pushMessage('/e5/unrouted'), 'e5-provider');

		self::assertSame([], $this->classes());

		$message = $this->pushMessage('/e5/probe');

		$this->application()->handlePush($message, 'e5-provider');

		self::assertSame([Events\PushEvent::class], $this->classes());

		$push = $this->dispatched[0];
		assert($push instanceof Events\PushEvent);

		self::assertSame($message, $push->getMessage());
		self::assertSame('e5-provider', $push->getProvider());
		self::assertInstanceOf(Topics\Topic::class, $push->getTopic());
		self::assertSame('/e5/probe', $push->getTopic()->getId());
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
		$request = $this->createMock(Handshake\IRequest::class);
		$request->method('getHeader')
			->willReturn(null);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')
			->willReturn(634);
		$client->method('isHttpHeadersReceived')
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

	private function pushMessage(string $topic): PushMessages\IMessage&MockObject
	{
		$message = $this->createMock(PushMessages\IMessage::class);
		$message->method('getTopic')
			->willReturn($topic);
		$message->method('getData')
			->willReturn(['e5' => 'probe']);

		return $message;
	}

}
