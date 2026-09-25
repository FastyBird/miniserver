<?php declare(strict_types = 1);

/**
 * SocketRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Router;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Wamp;
use Nette;

/**
 * Module sockets routes configuration
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SocketRoutes
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	public static function createRouter(): Wamp\RouteList
	{
		$router = new Wamp\RouteList();
		$router[] = new Wamp\WampRoute(
			'/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/exchange',
			'UiModule:Exchange:',
		);

		return $router;
	}

}
