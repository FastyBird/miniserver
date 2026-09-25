<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;

/**
 * A controller that does nothing, used to let Application::processMessage() complete without
 * throwing when a test needs to reach code past it without exercising the routing/dispatch it
 * performs.
 */
final class DummyWebSocketsController implements Controllers\RequestController
{

	public function run(Controllers\Request $request): Responses\ControllerResponse
	{
		return new Responses\NullResponse();
	}

	public function getName(): string
	{
		return 'dummy';
	}

}
