<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Router;

use FastyBird\Library\WebSockets\Application;
use FastyBird\Library\WebSockets\Http;

/**
 * Router interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IRouter
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
