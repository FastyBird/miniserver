<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use Exception;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Wamp;
use Nette\DI;
use Nette\Http;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher;

/**
 * Application's open, close, message and error hooks used to fire only through
 * SmartObject::__call, and then through Utils\Arrays::invoke(). Since #638 they are the
 * ConnectionOpened, ConnectionClosed, ApplicationMessageReceived and ApplicationFailed events;
 * these guard that a listener of each gets the same arguments the old handlers did.
 */
final class ApplicationTest extends TestCase
{

	private EventDispatcher\EventDispatcher $dispatcher;

	private function createApplication(): Controllers\Application
	{
		$this->dispatcher = new EventDispatcher\EventDispatcher();

		$router = $this->createMock(Wamp\WampRouter::class);
		$controllerFactory = new Controllers\ControllerFactory(new DI\Container());
		$clientsStorage = new Clients\Storage();

		return new class(
			$router,
			$controllerFactory,
			$clientsStorage,
			$this->dispatcher,
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
		$client = $this->createMock(Entities\Client::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$received = [];
		$this->dispatcher->addListener(
			Events\ConnectionOpened::class,
			static function (Events\ConnectionOpened $event) use (&$received): void {
				$received = [$event->getApplication(), $event->getClient(), $event->getHttpRequest()];
			},
		);

		$application->handleOpen($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnCloseFiresRegisteredHandlerWithApplicationClientAndRequest(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\Client::class);
		$client->method('getId')
			->willReturn(1);
		$httpRequest = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$received = [];
		$this->dispatcher->addListener(
			Events\ConnectionClosed::class,
			static function (Events\ConnectionClosed $event) use (&$received): void {
				$received = [$event->getApplication(), $event->getClient(), $event->getHttpRequest()];
			},
		);

		$application->handleClose($client, $httpRequest);

		self::assertSame([$application, $client, $httpRequest], $received);
	}

	public function testOnMessageFiresRegisteredHandlerWithApplicationClientRequestAndMessage(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\Client::class);
		$httpRequest = new Handshake\Request(new Http\UrlScript('ws://localhost/'));

		$received = [];
		$this->dispatcher->addListener(
			Events\ApplicationMessageReceived::class,
			static function (Events\ApplicationMessageReceived $event) use (&$received): void {
				$received = [$event->getApplication(), $event->getClient(), $event->getHttpRequest(), $event->getMessage()];
			},
		);

		$application->handleMessage($client, $httpRequest, 'payload');

		self::assertSame([$application, $client, $httpRequest, 'payload'], $received);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testOnErrorFiresRegisteredHandlerWithApplicationClientRequestAndException(): void
	{
		$application = $this->createApplication();
		$client = $this->createMock(Entities\Client::class);
		$client->expects(self::once())
			->method('close');
		$httpRequest = new Handshake\Request(new Http\UrlScript('ws://localhost/'));
		$exception = new class('boom', 0) extends Exception
		{

		};

		$received = [];
		$this->dispatcher->addListener(
			Events\ApplicationFailed::class,
			static function (Events\ApplicationFailed $event) use (&$received): void {
				$received = [$event->getApplication(), $event->getClient(), $event->getHttpRequest(), $event->getException()];
			},
		);

		$application->handleError($client, $httpRequest, $exception);

		self::assertSame([$application, $client, $httpRequest, $exception], $received);
	}

}
