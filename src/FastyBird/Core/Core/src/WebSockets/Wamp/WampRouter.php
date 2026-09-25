<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Wamp;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Handshake;

/**
 * WAMP router interface
 */
interface WampRouter
{

	/**
	 * Convert incoming message to the request, if not match return null
	 */
	public function match(Handshake\IRequest $httpRequest): Controllers\Request|null;

	/**
	 * Constructs absolute URL from Request object
	 */
	public function constructUrl(Controllers\DispatchRequest $appRequest): string|null;

}
