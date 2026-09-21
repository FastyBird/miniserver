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

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Routing as WebSocketsRouting;

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
	 */
	public static function createRouter(): WebSocketsRouting\RouteList
	{
		$router = new WebSocketsRouting\RouteList();
		$router[] = new WebSocketsRouting\WampRoute(
			'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/exchange',
			'DevicesModule:Exchange:',
		);

		return $router;
	}

}
