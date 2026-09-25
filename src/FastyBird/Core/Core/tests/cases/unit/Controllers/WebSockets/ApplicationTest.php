<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use Exception;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Wamp;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Application::$onOpen/$onClose/$onMessage/$onError used to fire only through
 * SmartObject::__call. These guard that Utils\Arrays::invoke() reaches every registered handler
 * with the same arguments the old magic call did.
 */
final class ApplicationTest extends TestCase
{

	private function createApplication(): Controllers\Application
	{
		$router = $this->createMock(Wamp\WampRouter::class);
		$controllerFactory = $this->createMock(Controllers\IControllerFactory::class);
		$clientsStorage = $this->createMock(Clients\IStorage::class);

		return new class(
			$router,
			$controllerFactory,
			$clientsStorage,
		) extends Controllers\Application
		{

			/**
			 * @return array<string>
			 */
			public function getSubProtocols(): array
			{
				return [];
			}

		};
	}

	public function testOnOpenFiresRegisteredHandlerWithApplicationClientAndRequest(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = $this->createMock(Handshake\IRequest::class);

		$received = [];
		$application->onOpen[] = static function (
			Controllers\Application $a,
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
		) use (&$received): void {
			$received = [$a, $c, $r];
		};

		$application->handleOpen($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnCloseFiresRegisteredHandlerWithApplicationClientAndRequest(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = $this->createMock(Handshake\IRequest::class);

		$received = [];
		$application->onClose[] = static function (
			Controllers\Application $a,
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
		) use (&$received): void {
			$received = [$a, $c, $r];
		};

		$application->handleClose($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnMessageFiresRegisteredHandlerWithApplicationClientRequestAndMessage(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\ConnectedClient::class);
		$httpRequest = $this->createMock(Handshake\IRequest::class);

		$received = [];
		$application->onMessage[] = static function (
			Controllers\Application $a,
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
			string $m,
		) use (&$received): void {
			$received = [$a, $c, $r, $m];
		};

		$application->handleMessage($client, $httpRequest, 'payload');

		self::assertSame([$application, $client, $httpRequest, 'payload'], $received);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testOnErrorFiresRegisteredHandlerWithApplicationClientRequestAndException(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->expects(self::once())
			->method('close');
		$httpRequest = $this->createMock(Handshake\IRequest::class);
		$exception = new class('boom', 0) extends Exception
		{

		};

		$received = [];
		$application->onError[] = static function (
			Controllers\Application $a,
			Entities\ConnectedClient $c,
			Handshake\IRequest $r,
			Throwable $e,
		) use (&$received): void {
			$received = [$a, $c, $r, $e];
		};

		$application->handleError($client, $httpRequest, $exception);

		self::assertSame([$application, $client, $httpRequest, $exception], $received);
	}

}
