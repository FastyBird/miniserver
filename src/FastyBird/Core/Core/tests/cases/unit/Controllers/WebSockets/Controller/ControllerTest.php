<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets\Controller;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Routing;
use FastyBird\Core\WebSockets\Wamp;
use Nette\DI;
use Nette\InvalidStateException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TypeError;

/**
 * Controller::$payload used to be documented as a SmartObject magic property. Two consumers
 * outside Core (Module\Devices and Module\Ui's ExchangeV1 controllers) wrote to it as
 * `$this->payload->data = [...]` directly, which only worked because SmartObject's __set
 * intercepted the otherwise-private property from the subclass's scope; removing the trait broke
 * both silently until they were switched to the getPayload() accessor. This guards that the
 * accessor returns the same stdClass instance sendPayload() reads from, which is what makes the
 * accessor a safe substitute for the direct (now impossible) property write.
 *
 * Controller::$controllerFactory used to be a non-nullable typed property with no default, and
 * injectPrimary() read it via `!== null` (which throws on an uninitialized typed property, same as
 * a direct read) before ever assigning it. Every single controller creation hit that,
 * unconditionally: DI's callInjects() calls injectPrimary() immediately after instantiating any
 * controller, so every WAMP SUBSCRIBE/CALL/PUBLISH dispatch crashed before the controller's own
 * action ever ran.
 *
 * `@User(loggedIn)` on a controller class or action must fail closed with InvalidState (#650,
 * #652). It used to reach Controller::getUser(), which always threw InvalidState because
 * nette/security is not installed and no Nette\Security\User service could exist; getUser() is
 * gone, and checkRequirements() now throws the same exception class itself. Deleting the check
 * instead would have turned the annotation into a silent fail-open.
 */
final class ControllerTest extends TestCase
{

	private const string LOGGED_IN_UNSUPPORTED = '@User(loggedIn) is not supported on WebSockets controllers: a client'
		. ' is authenticated at the handshake, and its roles are checked through ConnectedClient::getRoles().';

	public function testGetPayloadReturnsSameInstanceSendPayloadReads(): void
	{
		$controller = new class extends Controllers\Controller
		{

		};

		$controller->getPayload()->data = ['response' => 'accepted'];

		self::assertSame(['response' => 'accepted'], $controller->getPayload()->data);
	}

	/**
	 * @throws InvalidStateException
	 */
	public function testInjectPrimarySucceedsOnceAndRejectsASecondCall(): void
	{
		$controller = new class extends Controllers\Controller
		{

		};

		$controllerFactory = new Controllers\ControllerFactory(new DI\Container());
		$router = $this->createMock(Wamp\WampRouter::class);
		$linkGenerator = new Routing\LinkGenerator($router);

		$controller->injectPrimary($controllerFactory, $router, $linkGenerator);

		self::expectException(InvalidStateException::class);

		$controller->injectPrimary($controllerFactory, $router, $linkGenerator);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 * @throws TypeError
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws WebSocketsExceptions\BadSignal
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 */
	public function testUserLoggedInAnnotationOnControllerClassFailsClosed(): void
	{
		$controller = new /** @User(loggedIn) */ class extends Controllers\Controller
		{

			public bool $actionRan = false;

			public function actionDefault(): void
			{
				$this->actionRan = true;
			}

		};

		try {
			$controller->run(new Controllers\Request('Test:Test'));

			self::fail('A controller class annotated @User(loggedIn) must not run.');
		} catch (CoreExceptions\InvalidState $ex) {
			self::assertSame(self::LOGGED_IN_UNSUPPORTED, $ex->getMessage());
		}

		self::assertFalse($controller->actionRan);
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws ReflectionException
	 * @throws TypeError
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws WebSocketsExceptions\BadSignal
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 */
	public function testUserLoggedInAnnotationOnControllerActionFailsClosed(): void
	{
		$controller = new class extends Controllers\Controller
		{

			public bool $actionRan = false;

			/**
			 * @User(loggedIn)
			 */
			public function actionDefault(): void
			{
				$this->actionRan = true;
			}

		};

		try {
			$controller->run(new Controllers\Request('Test:Test'));

			self::fail('A controller action annotated @User(loggedIn) must not run.');
		} catch (CoreExceptions\InvalidState $ex) {
			self::assertSame(self::LOGGED_IN_UNSUPPORTED, $ex->getMessage());
		}

		self::assertFalse($controller->actionRan);
	}

}
