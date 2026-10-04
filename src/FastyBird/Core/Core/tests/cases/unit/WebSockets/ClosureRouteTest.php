<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Wamp;
use Nette;
use Nette\DI;
use Nette\Http;
use Nette\Utils;
use ReflectionException;
use Throwable;

/**
 * Characterization of a WAMP route whose action is a closure, resolved through RouteList and the
 * container's ControllerFactory (#460 §1.10, §3.11; E5.5 #637 replaces the iPub strings).
 *
 * WampRoute turns a closure action into the controller name `IPub:WebSocket` with the closure as
 * its `callback` parameter, and RouteList leaves a name starting with `IPub:` without its
 * module prefix. No controller class answers to that name: ControllerFactory maps it, through
 * its catch-all mapping, to a class that does not exist and refuses it. So today a closure
 * route matches but cannot be dispatched -- a KNOWN DEFECT (census X5, T12-12). This pins
 * exactly that, strings included, so the census's replacement values (T9) are the only change
 * E5.5 can make here.
 *
 * Measured, against census X5: Application::processMessage() does not get as far as its
 * BadRequest. ControllerFactory::getControllerClass() throws InvalidController first, and a WAMP
 * call reports that exception's message in its call error.
 */
final class ClosureRouteTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\OutOfRangeException
	 */
	public function testAClosureRouteMatchesAsTheIpubWebSocketControllerWithItsCallback(): void
	{
		$callback = static fn (): string => 'e5';

		$routes = new Wamp\RouteList('Probe');
		$routes[] = new Wamp\WampRoute('/e5/closure', $callback);

		$request = $routes->match($this->request('ws://localhost/e5/closure'));

		self::assertInstanceOf(Controllers\Request::class, $request);
		self::assertSame('IPub:WebSocket', $request->getControllerName());
		self::assertSame($callback, $request->getParameters()['callback'] ?? null);

		self::assertNull($routes->match($this->request('ws://localhost/e5/other')));
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidController
	 * @throws ReflectionException
	 */
	public function testTheControllerFactoryHasNoControllerForAClosureRoute(): void
	{
		$factory = $this->container->getByType(Controllers\IControllerFactory::class);

		self::assertInstanceOf(Controllers\ControllerFactory::class, $factory);
		self::assertSame('IPubModule\WebSocketController', $factory->formatControllerClass('IPub:WebSocket'));

		$this->expectException(Exceptions\InvalidController::class);
		$this->expectExceptionMessage(
			'Cannot load controller "IPub:WebSocket", class "IPubModule\WebSocketController" was not found.',
		);

		$name = 'IPub:WebSocket';
		$factory->getControllerClass($name);
	}

	/**
	 * A WAMP call to a topic a closure route takes reaches Application::processMessage(), which
	 * cannot load a controller for it; the client gets the WAMP call error with that message.
	 * A KNOWN DEFECT (census X5): closure routes cannot dispatch today.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 * @throws Throwable
	 */
	public function testACallToAClosureRouteEndsInTheCallErrorOfTheMissingController(): void
	{
		$router = $this->container->getByType(Wamp\WampRouter::class);
		self::assertInstanceOf(Wamp\RouteList::class, $router);
		$router[] = new Wamp\WampRoute('/e5/closure', static fn (): string => 'e5');

		$sent = [];

		$client = $this->createMock(Entities\ConnectedClient::class);
		$client->method('getId')
			->willReturn(634);
		$client->method('getParameter')
			->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $default);
		$client->method('send')
			->willReturnCallback(static function (mixed $response) use (&$sent): void {
				$sent[] = $response;
			});

		$this->container->getByType(Controllers\WampApplication::class)->handleMessage(
			$client,
			$this->request('ws://localhost/'),
			'[2, "e5-call", "/e5/closure", {}]',
		);

		self::assertCount(1, $sent);
		self::assertIsString($sent[0]);
		self::assertSame(
			[
				Controllers\WampApplication::MSG_CALL_ERROR,
				'e5-call',
				'/e5/closure',
				'Cannot load controller "IPub:WebSocket", class "IPubModule\WebSocketController" was not found.',
				['code' => 0, 'params' => []],
			],
			Utils\Json::decode($sent[0], forceArrays: true),
		);
	}

	private function request(string $url): Handshake\Request
	{
		return new Handshake\Request(new Http\UrlScript($url));
	}

}
