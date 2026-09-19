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

use FastyBird\Library\Metadata;
use FastyBird\Library\WebSockets;

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
	 * @throws WebSockets\Exceptions\InvalidArgument
	 */
	public static function createRouter(): WebSockets\Router\RouteList
	{
		$router = new WebSockets\Router\RouteList();
		$router[] = new WebSockets\Router\Route(
			'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/exchange',
			'DevicesModule:Exchange:',
		);

		return $router;
	}

}
