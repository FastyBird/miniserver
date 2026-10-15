<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Router;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Wamp;
use FastyBird\Module\Ui;
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
			'/' . Ui\DI\UiExtension::MODULE_UI_PREFIX . '/v1/exchange',
			'UiModule:Exchange:',
		);

		return $router;
	}

}
