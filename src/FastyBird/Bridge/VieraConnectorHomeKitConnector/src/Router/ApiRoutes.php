<?php declare(strict_types = 1);

/**
 * ApiRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Router;

use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Controllers;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Module\Devices\Middleware as DevicesMiddleware;

/**
 * Bridge API routes configuration
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiRoutes
{

	public const URL_ITEM_ID = 'id';

	public const RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Controllers\BridgesV1 $bridgesV1Controller,
		private readonly DevicesMiddleware\Access $devicesAccessControlMiddleware,
		private readonly SecurityMiddleware\Authorization $authorizationMiddleware,
		private readonly SecurityMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(Routing\IRouter $router): void
	{
		$routes = $router->group('/' . Metadata\Constants::ROUTER_API_PREFIX, function (
			Routing\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group(
					'/' . Metadata\Constants::BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX,
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
		Routing\IRouter|Routing\IRouteCollector $group,
	): Routing\IRouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			/**
			 * BRIDGES
			 */
			$group->group('/bridges', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->bridgesV1Controller, 'index']);
				$route->setName(VieraConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'read']);
				$route->setName(VieraConnectorHomeKitConnector\Constants::ROUTE_NAME_BRIDGE);

				$group->post('', [$this->bridgesV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->bridgesV1Controller, 'delete']);
			});
		});
	}

}
