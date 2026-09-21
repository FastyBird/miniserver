<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Http;

/**
 * WAMP router interface
 *
 * @package        FastyBird:Core!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IWampRouter
{

	/**
	 * Convert incoming message to the request, if not match return null
	 */
	public function match(Http\IRequest $httpRequest): Application\Request|null;

	/**
	 * Constructs absolute URL from Request object
	 */
	public function constructUrl(Application\IRequest $appRequest): string|null;

}
