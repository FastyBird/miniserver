<?php declare(strict_types = 1);

/**
 * AppRouter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\Core\Routing;

use Nette\Application;

/**
 * Application router
 *
 * @package        FastyBird:Application!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AppRouter
{

	public static function createRouter(Application\Routers\RouteList $router): void
	{
		$list = $router->withModule('App');

		$list->addRoute('/', [
			'presenter' => 'Default',
			'action' => 'default',
		]);
	}

}
