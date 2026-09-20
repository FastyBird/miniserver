<?php declare(strict_types = 1);

/**
 * ApiRoutes.php
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

use FastyBird\Core\Middleware\SimpleAuth as SimpleAuthMiddleware;
use FastyBird\Core\Constants\Metadata;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Middleware;

/**
 * Module API routes configuration
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiRoutes
{

	public const URL_ITEM_ID = 'id';

	public const URL_DEVICE_ID = 'device';

	public const URL_CHANNEL_ID = 'channel';

	public const URL_CONNECTOR_ID = 'connector';

	public const URL_PROPERTY_ID = 'property';

	public const RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Controllers\DevicesV1 $devicesV1Controller,
		private readonly Controllers\DeviceParentsV1 $deviceParentsV1Controller,
		private readonly Controllers\DeviceChildrenV1 $deviceChildrenV1Controller,
		private readonly Controllers\DevicePropertiesV1 $devicePropertiesV1Controller,
		private readonly Controllers\DevicePropertyChildrenV1 $devicePropertyChildrenV1Controller,
		private readonly Controllers\DevicePropertyStateV1 $devicePropertyStateV1Controller,
		private readonly Controllers\DeviceControlsV1 $deviceControlsV1Controller,
		private readonly Controllers\ChannelsV1 $channelsV1Controller,
		private readonly Controllers\ChannelPropertiesV1 $channelPropertiesV1Controller,
		private readonly Controllers\ChannelPropertyChildrenV1 $channelPropertyChildrenV1Controller,
		private readonly Controllers\ChannelPropertyStateV1 $channelPropertyStateV1Controller,
		private readonly Controllers\ChannelControlsV1 $channelControlsV1Controller,
		private readonly Controllers\ConnectorsV1 $connectorsV1Controller,
		private readonly Controllers\ConnectorPropertiesV1 $connectorPropertiesV1Controller,
		private readonly Controllers\ConnectorPropertyStateV1 $connectorPropertyStateV1Controller,
		private readonly Controllers\ConnectorControlsV1 $connectorControlsV1Controller,
		private readonly Middleware\Access $devicesAccessControlMiddleware,
		private readonly Middleware\UrlFormat $urlFormatlMiddleware,
		private readonly SimpleAuthMiddleware\Authorization $accessControlMiddleware,
		private readonly SimpleAuthMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(SlimRouterRouting\IRouter $router): void
	{
		$routes = $router->group('/' . Metadata\Constants::ROUTER_API_PREFIX, function (
			SlimRouterRouting\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group('/' . Metadata\Constants::MODULE_DEVICES_PREFIX, function (
					SlimRouterRouting\RouteCollector $group,
				): void {
					$this->buildRoutes($group);
				});

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->accessControlMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->devicesAccessControlMiddleware);
		$routes->addMiddleware($this->urlFormatlMiddleware);
	}

	private function buildRoutes(SlimRouterRouting\IRouter|SlimRouterRouting\IRouteCollector $group): SlimRouterRouting\IRouteGroup
	{
		return $group->group('/v1', function (SlimRouterRouting\RouteCollector $group): void {
			/**
			 * CHANNELS
			 */
			$group->group('/channels', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->channelsV1Controller, 'index']);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNELS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'read']);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL);

				$route = $group->post('', [$this->channelsV1Controller, 'create']);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'update']);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'delete']);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL);

				$route = $group->get(
					'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
					[
						$this->channelsV1Controller,
						'readRelationship',
					],
				);
				$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_RELATIONSHIP);

				$group->group(
					'/{' . self::URL_CHANNEL_ID . '}',
					function (SlimRouterRouting\RouteCollector $group): void {
						/**
						 * CHANNEL PROPERTIES
						 */
						$group->group('/properties', function (SlimRouterRouting\RouteCollector $group): void {
							$route = $group->get('', [$this->channelPropertiesV1Controller, 'index']);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTIES);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'read',
							]);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->post('', [$this->channelPropertiesV1Controller, 'create']);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'update',
							]);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'delete',
							]);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->channelPropertiesV1Controller,
									'readRelationship',
								],
							);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP);

							$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
								SlimRouterRouting\RouteCollector $group,
							): void {
								/**
								 * CHILDREN
								 */
								$route = $group->get('/children', [
									$this->channelPropertyChildrenV1Controller,
									'index',
								]);
								$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN);

								/**
								 * STATE
								 */
								$route = $group->get(
									'/state',
									[$this->channelPropertyStateV1Controller, 'index'],
								);
								$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_STATE);
							});
						});

						/**
						 * CHANNEL CONTROLS
						 */
						$group->group('/controls', function (SlimRouterRouting\RouteCollector $group): void {
							$route = $group->get('', [$this->channelControlsV1Controller, 'index']);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROLS);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->channelControlsV1Controller,
								'read',
							]);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->channelControlsV1Controller,
									'readRelationship',
								],
							);
							$route->setName(Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP);
						});
					},
				);
			});

			/**
			 * DEVICES
			 */
			$group->group('/devices', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->devicesV1Controller, 'index']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'read']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE);

				$route = $group->post('', [$this->devicesV1Controller, 'create']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'update']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'delete']);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->devicesV1Controller,
					'readRelationship',
				]);
				$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP);

				$group->group('/{' . self::URL_DEVICE_ID . '}', function (SlimRouterRouting\RouteCollector $group): void {
					/**
					 * PARENTS
					 */
					$route = $group->get('/parents', [$this->deviceParentsV1Controller, 'index']);
					$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PARENTS);

					/**
					 * CHILDREN
					 */
					$route = $group->get('/children', [$this->deviceChildrenV1Controller, 'index']);
					$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHILDREN);

					/**
					 * DEVICE PROPERTIES
					 */
					$group->group('/properties', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->devicePropertiesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->devicePropertiesV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY);

						$route = $group->post('', [$this->devicePropertiesV1Controller, 'create']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY);

						$route = $group->patch(
							'/{' . self::URL_ITEM_ID . '}',
							[$this->devicePropertiesV1Controller, 'update'],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY);

						$route = $group->delete(
							'/{' . self::URL_ITEM_ID . '}',
							[$this->devicePropertiesV1Controller, 'delete'],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->devicePropertiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP);

						$group->group(
							'/{' . self::URL_PROPERTY_ID . '}',
							function (SlimRouterRouting\RouteCollector $group): void {
								/**
								 * CHILDREN
								 */
								$route = $group->get('/children', [$this->devicePropertyChildrenV1Controller, 'index']);
								$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_CHILDREN);

								/**
								 * STATE
								 */
								$route = $group->get('/state', [$this->devicePropertyStateV1Controller, 'index']);
								$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_STATE);
							},
						);
					});

					/**
					 * DEVICE CONTROLS
					 */
					$group->group('/controls', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->deviceControlsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROLS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->deviceControlsV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROL);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->deviceControlsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP);
					});

					/**
					 * CHANNELS
					 */
					$group->group('/channels', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->channelsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNELS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'read']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL);

						$route = $group->post('', [$this->channelsV1Controller, 'create']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL);

						$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'update']);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL);

						$route = $group->delete(
							'/{' . self::URL_ITEM_ID . '}',
							[$this->channelsV1Controller, 'delete'],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->channelsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP);

						$group->group(
							'/{' . self::URL_CHANNEL_ID . '}',
							function (SlimRouterRouting\RouteCollector $group): void {
								/**
								 * CHANNEL PROPERTIES
								 */
								$group->group('/properties', function (SlimRouterRouting\RouteCollector $group): void {
									$route = $group->get('', [$this->channelPropertiesV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

									$route = $group->post('', [$this->channelPropertiesV1Controller, 'create']);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

									$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'update',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

									$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
										$this->channelPropertiesV1Controller,
										'delete',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->channelPropertiesV1Controller,
											'readRelationship',
										],
									);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP);

									$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
										SlimRouterRouting\RouteCollector $group,
									): void {
										/**
										 * CHILDREN
										 */
										$route = $group->get('/children', [
											$this->channelPropertyChildrenV1Controller,
											'index',
										]);
										$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN);

										/**
										 * STATE
										 */
										$route = $group->get(
											'/state',
											[$this->channelPropertyStateV1Controller, 'index'],
										);
										$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE);
									});
								});

								/**
								 * CHANNEL CONTROLS
								 */
								$group->group('/controls', function (SlimRouterRouting\RouteCollector $group): void {
									$route = $group->get('', [$this->channelControlsV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROLS);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->channelControlsV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->channelControlsV1Controller,
											'readRelationship',
										],
									);
									$route->setName(Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP);
								});
							},
						);
					});
				});
			});

			/**
			 * CONNECTORS
			 */
			$group->group('/connectors', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->connectorsV1Controller, 'index']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTORS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'read']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR);

				$route = $group->post('', [$this->connectorsV1Controller, 'create']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'update']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'delete']);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->connectorsV1Controller,
					'readRelationship',
				]);
				$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_RELATIONSHIP);

				$group->group('/{' . self::URL_CONNECTOR_ID . '}', function (SlimRouterRouting\RouteCollector $group): void {
					/**
					 * CONNECTOR PROPERTIES
					 */
					$group->group('/properties', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->connectorPropertiesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY);

						$route = $group->post('', [$this->connectorPropertiesV1Controller, 'create']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY);

						$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'update',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY);

						$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorPropertiesV1Controller,
							'delete',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->connectorPropertiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP);

						$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
							SlimRouterRouting\RouteCollector $group,
						): void {
							/**
							 * STATE
							 */
							$route = $group->get('/state', [$this->connectorPropertyStateV1Controller, 'index']);
							$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY_STATE);
						});
					});

					/**
					 * CONNECTOR CONTROLS
					 */
					$group->group('/controls', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->connectorControlsV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROLS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
							$this->connectorControlsV1Controller,
							'read',
						]);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROL);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->connectorControlsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP);
					});

					/**
					 * DEVICES
					 */
					$group->group('/devices', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->devicesV1Controller, 'index']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'read']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE);

						$route = $group->post('', [$this->devicesV1Controller, 'create']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE);

						$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'update']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE);

						$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'delete']);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->devicesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP);

						$group->group(
							'/{' . self::URL_DEVICE_ID . '}',
							function (SlimRouterRouting\RouteCollector $group): void {
								/**
								 * DEVICE PROPERTIES
								 */
								$group->group('/properties', function (SlimRouterRouting\RouteCollector $group): void {
									$route = $group->get('', [$this->devicePropertiesV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->devicePropertiesV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

									$route = $group->post('', [$this->devicePropertiesV1Controller, 'create']);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

									$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
										$this->devicePropertiesV1Controller,
										'update',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

									$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
										$this->devicePropertiesV1Controller,
										'delete',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->devicePropertiesV1Controller,
											'readRelationship',
										],
									);
									$route->setName(
										Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP,
									);

									$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
										SlimRouterRouting\RouteCollector $group,
									): void {
										/**
										 * CHILDREN
										 */
										$route = $group->get('/children', [
											$this->devicePropertyChildrenV1Controller,
											'index',
										]);
										$route->setName(
											Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN,
										);

										/**
										 * STATE
										 */
										$route = $group->get(
											'/state',
											[$this->devicePropertyStateV1Controller, 'index'],
										);
										$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE);
									});
								});

								/**
								 * DEVICE CONTROLS
								 */
								$group->group('/controls', function (SlimRouterRouting\RouteCollector $group): void {
									$route = $group->get('', [$this->deviceControlsV1Controller, 'index']);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS);

									$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
										$this->deviceControlsV1Controller,
										'read',
									]);
									$route->setName(Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL);

									$route = $group->get(
										'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
										[
											$this->deviceControlsV1Controller,
											'readRelationship',
										],
									);
									$route->setName(
										Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP,
									);
								});
							},
						);
					});
				});
			});
		});
	}

}
