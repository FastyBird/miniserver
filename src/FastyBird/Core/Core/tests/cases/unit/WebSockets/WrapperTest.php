<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
use Nette\Http;
use PHPUnit\Framework\TestCase;
use React\Socket;
use RuntimeException;
use TypeError;

/**
 * Wrapper::$onClientConnected/$onClientDisconnected/$onClientError/$onIncomingMessage/
 * $onAfterIncomingMessage used to fire only through SmartObject::__call. These guard that
 * Utils\Arrays::invoke() reaches every registered handler with the same arguments the old magic
 * call did.
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
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getId')
			->willReturn(1);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();
		$clientsStorage->setStorageDriver(new Clients\Drivers\InMemory());
		$clientsStorage->addClient(1, $client);

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientDisconnected[] = static function (
			Entities\ConnectedClient $c,
			Handshake\Request $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

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
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientError[] = static function (
			Entities\ConnectedClient $c,
			Handshake\Request $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleError($client, new RuntimeException('boom'));

		self::assertSame([$client, $requestMock], $received);
	}

	public function testOnIncomingMessageAndOnAfterIncomingMessageFireWithClientRequestAndMessage(): void
	{
		$requestMock = new Handshake\Request(new Http\UrlScript('ws://localhost/'));
		$protocol = $this->createMock(Encoding\RFC6455::class);
		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = new Clients\Storage();

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$receivedIncoming = [];
		$wrapper->onIncomingMessage[] = static function (
			Entities\ConnectedClient $c,
			Handshake\Request $r,
			string $m,
		) use (&$receivedIncoming): void {
			$receivedIncoming = [$c, $r, $m];
		};

		$receivedAfter = [];
		$wrapper->onAfterIncomingMessage[] = static function (
			Entities\ConnectedClient $c,
			Handshake\Request $r,
		) use (&$receivedAfter): void {
			$receivedAfter = [$c, $r];
		};

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
		$client->method('isHttpHeadersReceived')
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

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientConnected[] = static function (
			Entities\ConnectedClient $c,
			Handshake\Request $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleMessage($client, 'irrelevant, headers already marked received');

		self::assertSame([$client, $requestMock], $received);
		self::assertTrue($webSocket->isEstablished());
	}

}
