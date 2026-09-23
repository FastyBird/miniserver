<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use FastyBird\Core\Clients\WsServer as ClientsWsServer;
use FastyBird\Core\Controllers\WebSockets\Controller;
use FastyBird\Core\Controllers\WebSockets\IRequest;
use FastyBird\Core\Controllers\WebSockets\Request;
use FastyBird\Core\Controllers\WebSockets\WampApplication;
use FastyBird\Core\Entities\WebSockets\PushMessages;
use FastyBird\Core\Entities\WsServer\Topics as WsServerTopics;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http as CoreHttp;
use FastyBird\Core\Routing as CoreRouting;
use FastyBird\Core\Tests\Fixtures\Dummy\DummyWebSocketsController;
use FastyBird\Core\Topics\WsServer as TopicsWsServer;
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
		$topic = new WsServerTopics\Topic('test/topic');

		$topicsStorage = $this->createMock(TopicsWsServer\IStorage::class);
		$topicsStorage->method('hasTopic')
			->willReturn(true);
		$topicsStorage->method('getTopic')
			->willReturn($topic);

		$router = new class implements CoreRouting\IWampRouter
		{

			public function match(CoreHttp\IRequest $httpRequest): Request
			{
				return new Request('test:module:controller');
			}

			public function constructUrl(IRequest $appRequest): string|null
			{
				return null;
			}

		};

		$controllerFactory = new class implements Controller\IControllerFactory
		{

			public function getControllerClass(string &$name): string
			{
				return DummyWebSocketsController::class;
			}

			public function createController(string $name): Controller\IController
			{
				return new DummyWebSocketsController();
			}

		};

		$clientsStorage = $this->createMock(ClientsWsServer\IStorage::class);

		$application = new WampApplication($topicsStorage, $router, $controllerFactory, $clientsStorage);

		$message = $this->createMock(PushMessages\IMessage::class);
		$message->method('getTopic')
			->willReturn('test/topic');
		$message->method('getData')
			->willReturn(['foo' => 'bar']);

		$received = [];
		$application->onPush[] = static function (
			PushMessages\IMessage $m,
			string $p,
			WsServerTopics\ITopic $t,
		) use (&$received): void {
			$received = [$m, $p, $t];
		};

		$application->handlePush($message, 'provider-x');

		self::assertSame([$message, 'provider-x', $topic], $received);
	}

}
