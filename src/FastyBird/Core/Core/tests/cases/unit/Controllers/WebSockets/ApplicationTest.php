<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use Exception;
use FastyBird\Core\Clients\WsServer as ClientsWsServer;
use FastyBird\Core\Controllers\WebSockets\Application;
use FastyBird\Core\Controllers\WebSockets\Controller;
use FastyBird\Core\Entities\WsServer as EntitiesWsServer;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Routing as CoreRouting;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Application::$onOpen/$onClose/$onMessage/$onError used to fire only through
 * SmartObject::__call. These guard that Utils\Arrays::invoke() reaches every registered handler
 * with the same arguments the old magic call did.
 */
final class ApplicationTest extends TestCase
{

	private function createApplication(): Application
	{
		$router = $this->createMock(CoreRouting\IWampRouter::class);
		$controllerFactory = $this->createMock(Controller\IControllerFactory::class);
		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);

		return new class(
			$router,
			$controllerFactory,
			$clientsStorage,
		) extends Application
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
		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = $this->createMock(Http\IRequest::class);

		$received = [];
		$application->onOpen[] = static function (
			Application $a,
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$received): void {
			$received = [$a, $c, $r];
		};

		$application->handleOpen($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnCloseFiresRegisteredHandlerWithApplicationClientAndRequest(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = $this->createMock(Http\IRequest::class);

		$received = [];
		$application->onClose[] = static function (
			Application $a,
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
		) use (&$received): void {
			$received = [$a, $c, $r];
		};

		$application->handleClose($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnMessageFiresRegisteredHandlerWithApplicationClientRequestAndMessage(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$httpRequest = $this->createMock(Http\IRequest::class);

		$received = [];
		$application->onMessage[] = static function (
			Application $a,
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
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
		$client = $this->createMock(EntitiesWsServer\IClient::class);
		$client->expects(self::once())
			->method('close');
		$httpRequest = $this->createMock(Http\IRequest::class);
		$exception = new class('boom', 0) extends Exception
		{

		};

		$received = [];
		$application->onError[] = static function (
			Application $a,
			EntitiesWsServer\IClient $c,
			Http\IRequest $r,
			Throwable $e,
		) use (&$received): void {
			$received = [$a, $c, $r, $e];
		};

		$application->handleError($client, $httpRequest, $exception);

		self::assertSame([$application, $client, $httpRequest, $exception], $received);
	}

}
