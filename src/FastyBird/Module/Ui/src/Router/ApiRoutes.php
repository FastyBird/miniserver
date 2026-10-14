<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Router;

use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Controllers;
use FastyBird\Module\Ui\Middleware as UiMiddleware;

/**
 * Module API routes configuration
 */
final readonly class ApiRoutes
{

	public const string URL_ITEM_ID = 'id';

	public const string URL_DASHBOARD_ID = 'dashboard';

	public const string URL_WIDGET_ID = 'widget';

	public const string RELATION_ENTITY = 'relationEntity';

	public const string ROUTE_NAME_DASHBOARDS = 'dashboards';

	public const string ROUTE_NAME_DASHBOARD = 'dashboard';

	public const string ROUTE_NAME_DASHBOARD_RELATIONSHIP = 'dashboard.relationship';

	public const string ROUTE_NAME_DASHBOARD_TABS = 'dashboard.tabs';

	public const string ROUTE_NAME_DASHBOARD_TAB = 'dashboard.tab';

	public const string ROUTE_NAME_DASHBOARD_TAB_RELATIONSHIP = 'dashboard.tab.relationship';

	public const string ROUTE_NAME_GROUPS = 'dashboard.groups';

	public const string ROUTE_NAME_GROUP = 'dashboard.group';

	public const string ROUTE_NAME_GROUP_RELATIONSHIP = 'dashboard.group.relationship';

	public const string ROUTE_NAME_WIDGETS = 'widgets';

	public const string ROUTE_NAME_WIDGET = 'widget';

	public const string ROUTE_NAME_WIDGET_RELATIONSHIP = 'widget.relationship';

	public const string ROUTE_NAME_WIDGET_DISPLAY = 'widget.display';

	public const string ROUTE_NAME_WIDGET_DISPLAY_RELATIONSHIP = 'widget.display.relationship';

	public const string ROUTE_NAME_WIDGET_DATA_SOURCES = 'widget.widget.data-sources';

	public const string ROUTE_NAME_WIDGET_DATA_SOURCE = 'widget.widget.data-source';

	public const string ROUTE_NAME_WIDGET_DATA_SOURCE_RELATIONSHIP = 'widget.widget.data-source.relationship';

	public function __construct(
		private bool $usePrefix,
		private Controllers\DashboardsV1 $dashboardsV1Controller,
		private Controllers\TabsV1 $tabsV1Controller,
		private Controllers\GroupsV1 $groupsV1Controller,
		private Controllers\WidgetsV1 $widgetsV1Controller,
		private Controllers\DisplayV1 $displayV1Controller,
		private Controllers\DataSourcesV1 $dataSourceV1Controller,
		private UiMiddleware\Access $uiAccessControlMiddleware,
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
				$group->group('/' . Ui\DI\UiExtension::MODULE_UI_PREFIX, function (
					Routing\RouteCollector $group,
				): void {
					$this->buildRoutes($group);
				});

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->authorizationMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->uiAccessControlMiddleware);
	}

	private function buildRoutes(
		Routing\Router|Routing\RouteCollector $group,
	): Routing\RouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			$group->group('/dashboards', function (Routing\RouteCollector $group): void {
				/**
				 * DASHBOARDS
				 */
				$route = $group->get('', [$this->dashboardsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_DASHBOARDS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->dashboardsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_DASHBOARD);

				$group->post('', [$this->dashboardsV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->dashboardsV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->dashboardsV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->dashboardsV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_DASHBOARD_RELATIONSHIP);
			});

			$group->group(
				'/dashboards/{' . self::URL_DASHBOARD_ID . '}',
				function (Routing\RouteCollector $group): void {
					$group->group('/tabs', function (Routing\RouteCollector $group): void {
						/**
						 * DASHBOARD TABS
						 */
						$route = $group->get('', [$this->tabsV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_DASHBOARD_TABS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->tabsV1Controller, 'read']);
						$route->setName(self::ROUTE_NAME_DASHBOARD_TAB);

						$group->post('', [$this->tabsV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->tabsV1Controller, 'update']);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->tabsV1Controller, 'delete']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->tabsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(self::ROUTE_NAME_DASHBOARD_TAB_RELATIONSHIP);
					});
				},
			);

			$group->group('/groups', function (Routing\RouteCollector $group): void {
				/**
				 * GROUPS
				 */
				$route = $group->get('', [$this->groupsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_GROUPS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->groupsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_GROUP);

				$group->post('', [$this->groupsV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->groupsV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->groupsV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->groupsV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_GROUP_RELATIONSHIP);
			});

			$group->group('/widgets', function (Routing\RouteCollector $group): void {
				/**
				 * WIDGETS
				 */
				$route = $group->get('', [$this->widgetsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_WIDGETS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->widgetsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_WIDGET);

				$group->post('', [$this->widgetsV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->widgetsV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->widgetsV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->widgetsV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_WIDGET_RELATIONSHIP);
			});

			$group->group(
				'/widgets/{' . self::URL_WIDGET_ID . '}',
				function (Routing\RouteCollector $group): void {
					$group->group('/display', function (Routing\RouteCollector $group): void {
						/**
						 * WIDGET DISPLAY
						 */
						$route = $group->get('', [$this->displayV1Controller, 'read']);
						$route->setName(self::ROUTE_NAME_WIDGET_DISPLAY);

						$group->patch('', [$this->displayV1Controller, 'update']);

						$route = $group->get('/relationships/{' . self::RELATION_ENTITY . '}', [
							$this->displayV1Controller,
							'readRelationship',
						]);
						$route->setName(self::ROUTE_NAME_WIDGET_DISPLAY_RELATIONSHIP);
					});

					$group->group('/data-sources', function (Routing\RouteCollector $group): void {
						/**
						 * WIDGET DATA SOURCES
						 */
						$route = $group->get('', [$this->dataSourceV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_WIDGET_DATA_SOURCES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->dataSourceV1Controller, 'read']);
						$route->setName(self::ROUTE_NAME_WIDGET_DATA_SOURCE);

						$group->post('', [$this->dataSourceV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->dataSourceV1Controller, 'update']);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->dataSourceV1Controller, 'delete']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->dataSourceV1Controller,
								'readRelationship',
							],
						);
						$route->setName(self::ROUTE_NAME_WIDGET_DATA_SOURCE_RELATIONSHIP);
					});
				},
			);
		});
	}

}
