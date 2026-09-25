<?php declare(strict_types = 1);

/**
 * SocketRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           13.03.20
 */

namespace FastyBird\Module\Devices\Router;

use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Wamp;
use Nette;

/**
 * Module sockets routes configuration
 *
 * @package        FastyBird:DevicesModule!
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
			'/' . Constants::MODULE_DEVICES_PREFIX . '/v1/exchange',
			'DevicesModule:Exchange:',
		);

		return $router;
	}

}
