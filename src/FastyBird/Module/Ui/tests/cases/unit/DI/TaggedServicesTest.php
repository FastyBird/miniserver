<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Wamp;
use FastyBird\Module\Ui\Tests;
use Nette;
use Nette\Http;

/**
 * The module reaches Core through DI tags, and a tag that one side stops producing, or that
 * the other side stops reading, is not an error: the tagged service is simply never used.
 * Each test here follows one tag from the service the module registers to the Core runtime
 * that is supposed to pick it up.
 */
final class TaggedServicesTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * WebSocketsExtension::ROUTES_TAG: the module's socket routes service is collected
	 * into the WAMP router
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testTaggedSocketRoutesReachTheWampRouter(): void
	{
		$router = $this->getContainer()->getByType(Wamp\WampRouter::class);

		$request = $router->match(
			new Handshake\Request(new Http\UrlScript('ws://localhost:8888/ui-module/v1/exchange')),
		);

		self::assertNotNull($request);
		self::assertSame('UiModule:Exchange', $request->getControllerName());
	}

}
