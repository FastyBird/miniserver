<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Controllers\WebSockets\Controller;
use FastyBird\Core\Controllers\WebSockets\Request;
use FastyBird\Core\Controllers\WebSockets\Responses;

/**
 * A controller that does nothing, used to let Application::processMessage() complete without
 * throwing when a test needs to reach code past it without exercising the routing/dispatch it
 * performs.
 */
final class DummyWebSocketsController implements Controller\IController
{

	public function run(Request $request): Responses\IResponse
	{
		return new Responses\NullResponse();
	}

	public function getName(): string
	{
		return 'dummy';
	}

}
