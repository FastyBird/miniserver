<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets\Controller;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Wamp;
use Nette\DI\Container;
use Nette\InvalidStateException;
use PHPUnit\Framework\TestCase;

/**
 * Controller::$payload used to be documented as a SmartObject magic property. Two consumers
 * outside Core (Module\Devices and Module\Ui's ExchangeV1 controllers) wrote to it as
 * `$this->payload->data = [...]` directly, which only worked because SmartObject's __set
 * intercepted the otherwise-private property from the subclass's scope; removing the trait broke
 * both silently until they were switched to the getPayload() accessor. This guards that the
 * accessor returns the same stdClass instance sendPayload() reads from, which is what makes the
 * accessor a safe substitute for the direct (now impossible) property write.
 *
 * Controller::$controllerFactory and $user used to be non-nullable typed properties with no
 * default, and injectPrimary() read $controllerFactory via `!== null` (which throws on an
 * uninitialized typed property, same as a direct read) before ever assigning it, then
 * unconditionally assigned $user -- always null in this deployment, since nette/security is not
 * installed and no Nette\Security\User service exists to autowire -- onto the non-nullable $user
 * property (a TypeError). Every single controller creation hit one or the other, unconditionally:
 * DI's callInjects() calls injectPrimary() immediately after instantiating any controller, so
 * every WAMP SUBSCRIBE/CALL/PUBLISH dispatch crashed before the controller's own action ever ran.
 */
final class ControllerTest extends TestCase
{

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

		$controllerFactory = $this->createMock(Controllers\IControllerFactory::class);
		$router = $this->createMock(Wamp\WampRouter::class);
		$linkGenerator = new Routing\LinkGenerator($router);

		$controller->injectPrimary(new Container(), $controllerFactory, $router, $linkGenerator, null);

		self::expectException(InvalidStateException::class);

		$controller->injectPrimary(new Container(), $controllerFactory, $router, $linkGenerator, null);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws InvalidStateException
	 */
	public function testGetUserThrowsInvalidStateWhenNoUserServiceWasInjected(): void
	{
		$controller = new class extends Controllers\Controller
		{

		};

		$controllerFactory = $this->createMock(Controllers\IControllerFactory::class);
		$router = $this->createMock(Wamp\WampRouter::class);
		$linkGenerator = new Routing\LinkGenerator($router);

		$controller->injectPrimary(new Container(), $controllerFactory, $router, $linkGenerator, null);

		self::expectException(Exceptions\InvalidState::class);
		self::expectExceptionMessage('Service User has not been set.');

		$controller->getUser();
	}

}
