<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Server\Wrapper;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Server;
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
		$requestMock = $this->createMock(Handshake\IRequest::class);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getRequest')
			->willReturn($requestMock);
		$client->method('getId')
			->willReturn(1);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = $this->createMock(Clients\IStorage::class);
		$clientsStorage->expects(self::once())
			->method('removeClient')
			->with(1);

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientDisconnected[] = static function (
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
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
		$requestMock = $this->createMock(Handshake\IRequest::class);
		$protocol = $this->createMock(Encoding\IProtocol::class);
		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = $this->createMock(Clients\IStorage::class);

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientError[] = static function (
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleError($client, new RuntimeException('boom'));

		self::assertSame([$client, $requestMock], $received);
	}

	public function testOnIncomingMessageAndOnAfterIncomingMessageFireWithClientRequestAndMessage(): void
	{
		$requestMock = $this->createMock(Handshake\IRequest::class);
		$protocol = $this->createMock(Encoding\IProtocol::class);
		$webSocket = new Entities\WebSocket(true, false, $protocol);

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('isHttpHeadersReceived')
			->willReturn(true);
		$client->method('getWebSocket')
			->willReturn($webSocket);
		$client->method('getRequest')
			->willReturn($requestMock);

		$application = $this->createMock(Controllers\Dispatcher::class);
		$clientsStorage = $this->createMock(Clients\IStorage::class);

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$receivedIncoming = [];
		$wrapper->onIncomingMessage[] = static function (
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
			string $m,
		) use (&$receivedIncoming): void {
			$receivedIncoming = [$c, $r, $m];
		};

		$receivedAfter = [];
		$wrapper->onAfterIncomingMessage[] = static function (
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
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
		$requestMock = $this->createMock(Handshake\IRequest::class);
		$requestMock->method('getHeader')
			->willReturn(null);

		$protocol = $this->createMock(Encoding\IProtocol::class);
		$protocol->method('doHandshake')
			->willReturn(new Handshake\WampResponse(Handshake\IResponse::S101_SWITCHING_PROTOCOLS));

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

		$clientsStorage = $this->createMock(Clients\IStorage::class);

		$wrapper = new Server\Wrapper($application, $clientsStorage);

		$received = [];
		$wrapper->onClientConnected[] = static function (
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
		) use (&$received): void {
			$received = [$c, $r];
		};

		$wrapper->handleMessage($client, 'irrelevant, headers already marked received');

		self::assertSame([$client, $requestMock], $received);
		self::assertTrue($webSocket->isEstablished());
	}

}
