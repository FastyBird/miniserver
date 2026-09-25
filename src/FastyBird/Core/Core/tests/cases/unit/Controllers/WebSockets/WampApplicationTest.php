<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use FastyBird\Core\WebSockets\Controllers\WampApplication;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyWebSocketsController;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities\PushMessages;
use FastyBird\Core\WebSockets\Entities\Topics as EntitiesTopics;
use FastyBird\Core\WebSockets\Exceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Topics as WebSocketsTopics;
use FastyBird\Core\WebSockets\Wamp;
use PHPUnit\Framework\TestCase;

/**
 * WampApplication::$onPush used to fire only through SmartObject::__call. This guards that
 * Utils\Arrays::invoke() reaches every registered handler with the same arguments the old magic
 * call did.
 */
final class WampApplicationTest extends TestCase
{

	/**
	 * @throws Exceptions\Terminate
	 */
	public function testOnPushFiresRegisteredHandlerWithMessageProviderAndTopic(): void
	{
		$topic = new EntitiesTopics\Topic('test/topic');

		$topicsStorage = $this->createMock(WebSocketsTopics\IStorage::class);
		$topicsStorage->method('hasTopic')
			->willReturn(true);
		$topicsStorage->method('getTopic')
			->willReturn($topic);

		$router = new class implements Wamp\WampRouter
		{

			public function match(Handshake\IRequest $httpRequest): Controllers\Request
			{
				return new Controllers\Request('test:module:controller');
			}

			public function constructUrl(Controllers\DispatchRequest $appRequest): string|null
			{
				return null;
			}

		};

		$controllerFactory = new class implements Controllers\IControllerFactory
		{

			public function getControllerClass(string &$name): string
			{
				return DummyWebSocketsController::class;
			}

			public function createController(string $name): Controllers\RequestController
			{
				return new DummyWebSocketsController();
			}

		};

		$clientsStorage = $this->createMock(Clients\IStorage::class);

		$application = new Controllers\WampApplication($topicsStorage, $router, $controllerFactory, $clientsStorage);

		$message = $this->createMock(PushMessages\IMessage::class);
		$message->method('getTopic')
			->willReturn('test/topic');
		$message->method('getData')
			->willReturn(['foo' => 'bar']);

		$received = [];
		$application->onPush[] = static function (
			PushMessages\IMessage $m,
			string $p,
			EntitiesTopics\ITopic $t,
		) use (&$received): void {
			$received = [$m, $p, $t];
		};

		$application->handlePush($message, 'provider-x');

		self::assertSame([$message, 'provider-x', $topic], $received);
	}

}
