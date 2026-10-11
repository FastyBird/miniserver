<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Router;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Wamp;
use FastyBird\Module\Devices;
use Nette;

/**
 * Module sockets routes configuration
 */
final class SocketRoutes
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	public static function createRouter(): Wamp\RouteList
	{
		$router = new Wamp\RouteList();
		$router[] = new Wamp\WampRoute(
			'/' . Devices\Constants::MODULE_DEVICES_PREFIX . '/v1/exchange',
			'DevicesModule:Exchange:',
		);

		return $router;
	}

}
