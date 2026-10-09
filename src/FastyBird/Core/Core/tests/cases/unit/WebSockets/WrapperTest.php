<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use Nette\Http;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use React\Socket;
use RuntimeException;
use Symfony\Component\EventDispatcher;
use TypeError;

/**
 * Wrapper's client-connected, client-disconnected, client-error, incoming-message and
 * after-incoming-message hooks used to fire only through SmartObject::__call, and then through
 * Utils\Arrays::invoke(). Since #638 they are the ClientConnected, ClientDisconnected,
 * ClientFailed, MessageReceived and MessageProcessed events; these guard that a listener of each
 * gets the same arguments the old handlers did.
 */
final class WrapperTest extends TestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws WebSocketsExceptions\Storage
	 */
	public function testOnClientDisconnectedFiresRegisteredHandlerWithClientAndRequest(): void
	{
		$requestMock = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn(true);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getId')
			->willReturn(1);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();
		$clientsStorage->setStorageDriver(new Clients\Drivers\InMemory());
		$clientsStorage->addClient(1, $client);

		$dispatcher = new EventDispatcher\EventDispatcher();
		$wrapper = new Server\Wrapper($application, $clientsStorage, $dispatcher);

		$received = [];
		$dispatcher->addListener(
			Events\ClientDisconnected::class,
			static function (Events\ClientDisconnected $event) use (&$received): void {
				$received = [$event->getClient(), $event->getHttpRequest()];
			},
		);

		// the storage held the client, and closing removes exactly that one
		self::assertTrue($clientsStorage->hasClient(1));

		$wrapper->handleClose($client);

		self::assertSame([$client, $requestMock], $received);
		self::assertFalse($clientsStorage->hasClient(1));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testOnClientErrorFiresRegisteredHandlerWithClientAndRequest(): void
	{
		$requestMock = new Handshake\Request(new Http\UrlScript('ws://localhost/'));
		$protocol = $this->createMock(Encoding\RFC6455::class);
		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();

		$dispatcher = new EventDispatcher\EventDispatcher();
		$wrapper = new Server\Wrapper($application, $clientsStorage, $dispatcher);

		$received = [];
		$dispatcher->addListener(
			Events\ClientFailed::class,
			static function (Events\ClientFailed $event) use (&$received): void {
				$received = [$event->getClient(), $event->getHttpRequest()];
			},
		);

		$wrapper->handleError($client, new RuntimeException('boom'));

		self::assertSame([$client, $requestMock], $received);
	}

	public function testOnIncomingMessageAndOnAfterIncomingMessageFireWithClientRequestAndMessage(): void
	{
		$requestMock = new Handshake\Request(new Http\UrlScript('ws://localhost/'));
		$protocol = $this->createMock(Encoding\RFC6455::class);
		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();

		$dispatcher = new EventDispatcher\EventDispatcher();
		$wrapper = new Server\Wrapper($application, $clientsStorage, $dispatcher);

		$receivedIncoming = [];
		$dispatcher->addListener(
			Events\MessageReceived::class,
			static function (Events\MessageReceived $event) use (&$receivedIncoming): void {
				$receivedIncoming = [$event->getClient(), $event->getHttpRequest(), $event->getMessage()];
			},
		);

		$receivedAfter = [];
		$dispatcher->addListener(
			Events\MessageProcessed::class,
			static function (Events\MessageProcessed $event) use (&$receivedAfter): void {
				$receivedAfter = [$event->getClient(), $event->getHttpRequest()];
			},
		);

		$wrapper->handleMessage($client, 'payload');

		self::assertSame([$client, $requestMock, 'payload'], $receivedIncoming);
		self::assertSame([$client, $requestMock], $receivedAfter);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testOnClientConnectedFiresRegisteredHandlerWithClientAndRequestOnSuccessfulUpgrade(): void
	{
		$requestMock = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$protocol = $this->createMock(Encoding\RFC6455::class);
		$protocol->method('doHandshake')
			->willReturn(new Handshake\WampResponse(Handshake\WampResponse::S101_SWITCHING_PROTOCOLS));

		$webSocket = new Entities\WebSocket(false, false, $protocol);

		$connection = $this->createMock(Socket\ConnectionInterface::class);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method(PropertyHook::get('httpHeadersReceived'))
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getConnection')
			->willReturn($connection);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$application->expects(self::once())
			->method('handleOpen')
			->with($client, $requestMock);

		$clientsStorage = new Clients\Storage();

		$dispatcher = new EventDispatcher\EventDispatcher();
		$wrapper = new Server\Wrapper($application, $clientsStorage, $dispatcher);

		$received = [];
		$dispatcher->addListener(
			Events\ClientConnected::class,
			static function (Events\ClientConnected $event) use (&$received): void {
				$received = [$event->getClient(), $event->getHttpRequest()];
			},
		);

		$wrapper->handleMessage($client, 'irrelevant, headers already marked received');

		self::assertSame([$client, $requestMock], $received);
		self::assertTrue($webSocket->established);
	}

}
