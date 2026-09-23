<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Clients\WsServer as ClientsWsServer;
use FastyBird\Core\Controllers\WebSockets as ControllersWebSockets;
use FastyBird\Core\Encoding\WebSockets as EncodingWebSockets;
use FastyBird\Core\Entities\WebSockets as EntitiesWebSockets;
use FastyBird\Core\Entities\WsServer as EntitiesWsServer;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Server\WsServer\Wrapper;
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
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testOnClientDisconnectedFiresRegisteredHandlerWithClientAndRequest(): void
	{
		$requestMock = $this->createMock(Http\IRequest::class);

		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getId')
			->willReturn(1);

		$application = $this->createMock(ControllersWebSockets\IApplication::class);
		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);
		$clientsStorage->expects(self::once())
			->method('removeClient')
			->with(1);

		$wrapper = new Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientDisconnected[] = static function (
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleClose($client);

		self::assertSame([$client, $requestMock], $received);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testOnClientErrorFiresRegisteredHandlerWithClientAndRequest(): void
	{
		$requestMock = $this->createMock(Http\IRequest::class);
		$protocol = $this->createMock(EncodingWebSockets\IProtocol::class);
		$webSocket = new EntitiesWebSockets\WebSocket(true, false, $protocol);

		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(ControllersWebSockets\IApplication::class);
		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);

		$wrapper = new Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientError[] = static function (
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleError($client, new RuntimeException('boom'));

		self::assertSame([$client, $requestMock], $received);
	}

	public function testOnIncomingMessageAndOnAfterIncomingMessageFireWithClientRequestAndMessage(): void
	{
		$requestMock = $this->createMock(Http\IRequest::class);
		$protocol = $this->createMock(EncodingWebSockets\IProtocol::class);
		$webSocket = new EntitiesWebSockets\WebSocket(true, false, $protocol);

		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(ControllersWebSockets\IApplication::class);
		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);

		$wrapper = new Wrapper($application, $clientsStorage);

		$receivedIncoming = [];
		$wrapper->onIncomingMessage[] = static function (
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
			string $m,
		) use (&$receivedIncoming): void {
			$receivedIncoming = [$c, $r, $m];
		};

		$receivedAfter = [];
		$wrapper->onAfterIncomingMessage[] = static function (
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$receivedAfter): void {
			$receivedAfter = [$c, $r];
		};

		$wrapper->handleMessage($client, 'payload');

		self::assertSame([$client, $requestMock, 'payload'], $receivedIncoming);
		self::assertSame([$client, $requestMock], $receivedAfter);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 */
	public function testOnClientConnectedFiresRegisteredHandlerWithClientAndRequestOnSuccessfulUpgrade(): void
	{
		$requestMock = $this->createMock(Http\IRequest::class);
		$requestMock->method('getHeader')
			->willReturn(null);

		$protocol = $this->createMock(EncodingWebSockets\IProtocol::class);
		$protocol->method('doHandshake')
			->willReturn(new Http\WampResponse(Http\IResponse::S101_SWITCHING_PROTOCOLS));

		$webSocket = new EntitiesWebSockets\WebSocket(false, false, $protocol);

		$connection = $this->createMock(Socket\ConnectionInterface::class);

		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getConnection')
			->willReturn($connection);

		$application = $this->createMock(ControllersWebSockets\IApplication::class);
		$application->expects(self::once())
			->method('handleOpen')
			->with($client, $requestMock);

		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);

		$wrapper = new Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientConnected[] = static function (
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleMessage($client, 'irrelevant, headers already marked received');

		self::assertSame([$client, $requestMock], $received);
		self::assertTrue($webSocket->isEstablished());
	}

}
