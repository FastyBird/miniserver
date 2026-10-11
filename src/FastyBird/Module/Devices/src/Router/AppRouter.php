<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Router;

use Nette\Application;

/**
 * Application router
 */
final class AppRouter
{

	public static function createRouter(Application\Routers\RouteList $router): void
	{
		$list = $router->withModule('Devices');

		$list->addRoute('/devices/<id>/settings', [
			'presenter' => 'Devices',
			'action' => 'settings',
		]);

		$list->addRoute('/devices/<id>/settings/channel/add', [
			'presenter' => 'Channels',
			'action' => 'add',
		]);

		$list->addRoute('/devices/<id>/settings/channel/<channelId>', [
			'presenter' => 'Channels',
			'action' => 'edit',
		]);

		$list->addRoute('/devices[/<id>]', [
			'presenter' => 'Devices',
			'action' => 'default',
		]);

		$list->addRoute('/connectors/<id>/settings', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/add', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>/channel/add', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors/<id>/settings/device/<deviceId>/channel/<channelId>', [
			'presenter' => 'Connectors',
			'action' => 'settings',
		]);

		$list->addRoute('/connectors[/<id>]', [
			'presenter' => 'Connectors',
			'action' => 'default',
		]);
	}

}
