<?php declare(strict_types = 1);

namespace FastyBird\Core\Presenters;

use Nette\Application;

/**
 * Application router
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
