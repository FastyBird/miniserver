<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Router;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Controllers;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Module\Devices\Middleware as DevicesMiddleware;

/**
 * Bridge API routes configuration
 */
final readonly class ApiRoutes
{

	public const string URL_ITEM_ID = 'id';

	public const string RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private bool $usePrefix,
		private Controllers\BridgesV1 $bridgesV1Controller,
		private DevicesMiddleware\Access $devicesAccessControlMiddleware,
		private SecurityMiddleware\Authorization $authorizationMiddleware,
		private SecurityMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(Routing\Router $router): void
	{
		$routes = $router->group('/' . Routing\Router::API_PREFIX, function (
			Routing\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group(
					'/' . ShellyConnectorHomeKitConnector\Constants::BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX,
					function (
						Routing\RouteCollector $group,
					): void {
						$this->buildRoutes($group);
					},
				);

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->authorizationMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->devicesAccessControlMiddleware);
	}

	private function buildRoutes(
		Routing\Router|Routing\RouteCollector $group,
	): Routing\RouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			/**
			 * BRIDGES
			 */
			$group->group('/bridges', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->bridgesV1Controller, 'index']);
				$route->setName(ShellyConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'read']);
				$route->setName(ShellyConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGE);

				$group->post('', [$this->bridgesV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'delete']);
			});
		});
	}

}
