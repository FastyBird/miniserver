<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Router;

use Nette\Application;
use Nette\Routing;

/**
 * Application router
 */
final class AppRouter
{

	public static function createRouter(Application\Routers\RouteList $router): void
	{
		$list = $router->withModule('Accounts');

		$list->addRoute('/sign/<action>', [
			'presenter' => 'Sign',
			'action' => [
				Routing\Route::Pattern => 'in|up',
			],
		]);

		$list->addRoute('/reset-password', [
			'presenter' => 'Sign',
			'action' => 'reset',
		]);

		$list->addRoute('/account[/<action=default>]', [
			'presenter' => 'Account',
			'action' => [
				Routing\Route::Pattern => 'profile|password',
			],
		]);
	}

}
