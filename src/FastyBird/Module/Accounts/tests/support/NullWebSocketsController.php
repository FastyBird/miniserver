<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Support;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Controllers\Responses;
use Override;

/**
 * A WAMP controller that does nothing, for a topic a test subscribes a client to
 */
final class NullWebSocketsController implements Controllers\RequestController
{

	#[Override]
	public function run(Controllers\Request $request): Responses\ControllerResponse
	{
		return new Responses\NullResponse();
	}

	#[Override]
	public function getName(): string
	{
		return 'null';
	}

}
