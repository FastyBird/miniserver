<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Router;

use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Middleware as DevicesMiddleware;

/**
 * Module API routes configuration
 */
final readonly class ApiRoutes
{

	public const string URL_ITEM_ID = 'id';

	public const string URL_DEVICE_ID = 'device';

	public const string URL_CHANNEL_ID = 'channel';

	public const string URL_CONNECTOR_ID = 'connector';

	public const string URL_PROPERTY_ID = 'property';

	public const string RELATION_ENTITY = 'relationEntity';

	public const string ROUTE_NAME_DEVICES = 'devices';

	public const string ROUTE_NAME_DEVICE = 'device';

	public const string ROUTE_NAME_DEVICE_RELATIONSHIP = 'device.relationship';

	public const string ROUTE_NAME_DEVICE_PARENTS = 'device.parents';

	public const string ROUTE_NAME_DEVICE_CHILDREN = 'device.children';

	public const string ROUTE_NAME_DEVICE_PROPERTIES = 'device.properties';

	public const string ROUTE_NAME_DEVICE_PROPERTY = 'device.property';

	public const string ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP = 'device.property.relationship';

	public const string ROUTE_NAME_DEVICE_PROPERTY_CHILDREN = 'device.property.children';

	public const string ROUTE_NAME_DEVICE_PROPERTY_STATE = 'device.property.state';

	public const string ROUTE_NAME_DEVICE_CONTROLS = 'device.controls';

	public const string ROUTE_NAME_DEVICE_CONTROL = 'device.control';

	public const string ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP = 'device.control.relationship';

	public const string ROUTE_NAME_DEVICE_CHANNELS = 'device.channels';

	public const string ROUTE_NAME_DEVICE_CHANNEL = 'device.channel';

	public const string ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP = 'device.channel.relationship';

	public const string ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES = 'device.channel.properties';

	public const string ROUTE_NAME_DEVICE_CHANNEL_PROPERTY = 'device.channel.property';

	public const string ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP = 'device.channel.property.relationship';

	public const string ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN = 'device.channel.property.children';

	public const string ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE = 'device.channel.property.state';

	public const string ROUTE_NAME_DEVICE_CHANNEL_CONTROLS = 'device.channel.controls';

	public const string ROUTE_NAME_DEVICE_CHANNEL_CONTROL = 'device.channel.control';

	public const string ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP = 'device.channel.control.relationship';

	public const string ROUTE_NAME_CHANNELS = 'channels';

	public const string ROUTE_NAME_CHANNEL = 'channel';

	public const string ROUTE_NAME_CHANNEL_RELATIONSHIP = 'channel.relationship';

	public const string ROUTE_NAME_CHANNEL_PROPERTIES = 'channel.properties';

	public const string ROUTE_NAME_CHANNEL_PROPERTY = 'channel.property';

	public const string ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP = 'channel.property.relationship';

	public const string ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN = 'channel.property.children';

	public const string ROUTE_NAME_CHANNEL_PROPERTY_STATE = 'channel.property.state';

	public const string ROUTE_NAME_CHANNEL_CONTROLS = 'channel.controls';

	public const string ROUTE_NAME_CHANNEL_CONTROL = 'channel.control';

	public const string ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP = 'channel.control.relationship';

	public const string ROUTE_NAME_CONNECTORS = 'connectors';

	public const string ROUTE_NAME_CONNECTOR = 'connector';

	public const string ROUTE_NAME_CONNECTOR_RELATIONSHIP = 'connector.relationship';

	public const string ROUTE_NAME_CONNECTOR_PROPERTIES = 'connector.properties';

	public const string ROUTE_NAME_CONNECTOR_PROPERTY = 'connector.property';

	public const string ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP = 'connector.property.relationship';

	public const string ROUTE_NAME_CONNECTOR_PROPERTY_STATE = 'connector.property.state';

	public const string ROUTE_NAME_CONNECTOR_CONTROLS = 'connector.controls';

	public const string ROUTE_NAME_CONNECTOR_CONTROL = 'connector.control';

	public const string ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP = 'connector.control.relationship';

	public const string ROUTE_NAME_CONNECTOR_DEVICES = 'connector.devices';

	public const string ROUTE_NAME_CONNECTOR_DEVICE = 'connector.device';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP = 'connector.device.relationship';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES = 'connector.device.properties';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY = 'connector.device.property';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP = 'connector.device.property.relationship';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN = 'connector.device.property.children';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE = 'connector.device.property.state';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS = 'connector.device.controls';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_CONTROL = 'connector.device.control';

	public const string ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP = 'connector.device.control.relationship';

	public function __construct(
		private bool $usePrefix,
		private Controllers\DevicesV1 $devicesV1Controller,
		private Controllers\DeviceParentsV1 $deviceParentsV1Controller,
		private Controllers\DeviceChildrenV1 $deviceChildrenV1Controller,
		private Controllers\DevicePropertiesV1 $devicePropertiesV1Controller,
		private Controllers\DevicePropertyChildrenV1 $devicePropertyChildrenV1Controller,
		private Controllers\DevicePropertyStateV1 $devicePropertyStateV1Controller,
		private Controllers\DeviceControlsV1 $deviceControlsV1Controller,
		private Controllers\ChannelsV1 $channelsV1Controller,
		private Controllers\ChannelPropertiesV1 $channelPropertiesV1Controller,
		private Controllers\ChannelPropertyChildrenV1 $channelPropertyChildrenV1Controller,
		private Controllers\ChannelPropertyStateV1 $channelPropertyStateV1Controller,
		private Controllers\ChannelControlsV1 $channelControlsV1Controller,
		private Controllers\ConnectorsV1 $connectorsV1Controller,
		private Controllers\ConnectorPropertiesV1 $connectorPropertiesV1Controller,
		private Controllers\ConnectorPropertyStateV1 $connectorPropertyStateV1Controller,
		private Controllers\ConnectorControlsV1 $connectorControlsV1Controller,
		private DevicesMiddleware\Access $devicesAccessControlMiddleware,
		private SecurityMiddleware\Authorization $accessControlMiddleware,
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
				$group->group('/' . Devices\DI\DevicesExtension::MODULE_DEVICES_PREFIX, function (
					Routing\RouteCollector $group,
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
	}

	private function buildRoutes(
		Routing\Router|Routing\RouteCollector $group,
	): Routing\RouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			/**
			 * CHANNELS
			 */
			$group->group('/channels', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->channelsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_CHANNELS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_CHANNEL);

				$route = $group->post('', [$this->channelsV1Controller, 'create']);
				$route->setName(self::ROUTE_NAME_CHANNEL);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'update']);
				$route->setName(self::ROUTE_NAME_CHANNEL);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'delete']);
				$route->setName(self::ROUTE_NAME_CHANNEL);

				$route = $group->get(
					'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
					[
						$this->channelsV1Controller,
						'readRelationship',
					],
				);
				$route->setName(self::ROUTE_NAME_CHANNEL_RELATIONSHIP);

				$group->group(
					'/{' . self::URL_CHANNEL_ID . '}',
					function (Routing\RouteCollector $group): void {
						/**
						 * CHANNEL PROPERTIES
						 */
						$group->group('/properties', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->channelPropertiesV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTIES);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->post('', [$this->channelPropertiesV1Controller, 'create']);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'update',
							]);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
								$this->channelPropertiesV1Controller,
								'delete',
							]);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->channelPropertiesV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP);

							$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
								Routing\RouteCollector $group,
							): void {
								/**
								 * CHILDREN
								 */
								$route = $group->get('/children', [
									$this->channelPropertyChildrenV1Controller,
									'index',
								]);
								$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN);

								/**
								 * STATE
								 */
								$route = $group->get(
									'/state',
									[$this->channelPropertyStateV1Controller, 'read'],
								);
								$route->setName(self::ROUTE_NAME_CHANNEL_PROPERTY_STATE);
							});
						});

						/**
						 * CHANNEL CONTROLS
						 */
						$group->group('/controls', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->channelControlsV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_CHANNEL_CONTROLS);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->channelControlsV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_CHANNEL_CONTROL);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->channelControlsV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP);
						});
					},
				);
			});

			/**
			 * DEVICES
			 */
			$group->group('/devices', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->devicesV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_DEVICES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_DEVICE);

				$route = $group->post('', [$this->devicesV1Controller, 'create']);
				$route->setName(self::ROUTE_NAME_DEVICE);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'update']);
				$route->setName(self::ROUTE_NAME_DEVICE);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'delete']);
				$route->setName(self::ROUTE_NAME_DEVICE);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->devicesV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_DEVICE_RELATIONSHIP);

				$group->group(
					'/{' . self::URL_DEVICE_ID . '}',
					function (Routing\RouteCollector $group): void {
						/**
						 * PARENTS
						 */
						$route = $group->get('/parents', [$this->deviceParentsV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_DEVICE_PARENTS);

						/**
						 * CHILDREN
						 */
						$route = $group->get('/children', [$this->deviceChildrenV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_DEVICE_CHILDREN);

						/**
						 * DEVICE PROPERTIES
						 */
						$group->group('/properties', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->devicePropertiesV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTIES);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->devicePropertiesV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY);

							$route = $group->post('', [$this->devicePropertiesV1Controller, 'create']);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY);

							$route = $group->patch(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->devicePropertiesV1Controller, 'update'],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY);

							$route = $group->delete(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->devicePropertiesV1Controller, 'delete'],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->devicePropertiesV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP);

							$group->group(
								'/{' . self::URL_PROPERTY_ID . '}',
								function (Routing\RouteCollector $group): void {
									/**
									 * CHILDREN
									 */
									$route = $group->get(
										'/children',
										[$this->devicePropertyChildrenV1Controller, 'index'],
									);
									$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY_CHILDREN);

									/**
									 * STATE
									 */
									$route = $group->get('/state', [$this->devicePropertyStateV1Controller, 'read']);
									$route->setName(self::ROUTE_NAME_DEVICE_PROPERTY_STATE);
								},
							);
						});

						/**
						 * DEVICE CONTROLS
						 */
						$group->group('/controls', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->deviceControlsV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_DEVICE_CONTROLS);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->deviceControlsV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_DEVICE_CONTROL);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->deviceControlsV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP);
						});

						/**
						 * CHANNELS
						 */
						$group->group('/channels', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->channelsV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNELS);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->channelsV1Controller, 'read']);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL);

							$route = $group->post('', [$this->channelsV1Controller, 'create']);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL);

							$route = $group->patch(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->channelsV1Controller, 'update'],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL);

							$route = $group->delete(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->channelsV1Controller, 'delete'],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->channelsV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP);

							$group->group(
								'/{' . self::URL_CHANNEL_ID . '}',
								function (Routing\RouteCollector $group): void {
									/**
									 * CHANNEL PROPERTIES
									 */
									$group->group(
										'/properties',
										function (Routing\RouteCollector $group): void {
											$route = $group->get('', [$this->channelPropertiesV1Controller, 'index']);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES);

											$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
												$this->channelPropertiesV1Controller,
												'read',
											]);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

											$route = $group->post('', [$this->channelPropertiesV1Controller, 'create']);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

											$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
												$this->channelPropertiesV1Controller,
												'update',
											]);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

											$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
												$this->channelPropertiesV1Controller,
												'delete',
											]);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY);

											$route = $group->get(
												'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
												[
													$this->channelPropertiesV1Controller,
													'readRelationship',
												],
											);
											$route->setName(
												self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP,
											);

											$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
												Routing\RouteCollector $group,
											): void {
												/**
												 * CHILDREN
												 */
												$route = $group->get('/children', [
													$this->channelPropertyChildrenV1Controller,
													'index',
												]);
												$route->setName(
													self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN,
												);

												/**
													 * STATE
													 */
												$route = $group->get(
													'/state',
													[$this->channelPropertyStateV1Controller, 'read'],
												);
												$route->setName(
													self::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE,
												);
											});
										},
									);

									/**
									 * CHANNEL CONTROLS
									 */
									$group->group(
										'/controls',
										function (Routing\RouteCollector $group): void {
											$route = $group->get('', [$this->channelControlsV1Controller, 'index']);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_CONTROLS);

											$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
												$this->channelControlsV1Controller,
												'read',
											]);
											$route->setName(self::ROUTE_NAME_DEVICE_CHANNEL_CONTROL);

											$route = $group->get(
												'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
												[
													$this->channelControlsV1Controller,
													'readRelationship',
												],
											);
											$route->setName(
												self::ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP,
											);
										},
									);
								},
							);
						});
					},
				);
			});

			/**
			 * CONNECTORS
			 */
			$group->group('/connectors', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->connectorsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_CONNECTORS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_CONNECTOR);

				$route = $group->post('', [$this->connectorsV1Controller, 'create']);
				$route->setName(self::ROUTE_NAME_CONNECTOR);

				$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'update']);
				$route->setName(self::ROUTE_NAME_CONNECTOR);

				$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [$this->connectorsV1Controller, 'delete']);
				$route->setName(self::ROUTE_NAME_CONNECTOR);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->connectorsV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_CONNECTOR_RELATIONSHIP);

				$group->group(
					'/{' . self::URL_CONNECTOR_ID . '}',
					function (Routing\RouteCollector $group): void {
						/**
						 * CONNECTOR PROPERTIES
						 */
						$group->group('/properties', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->connectorPropertiesV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTIES);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->connectorPropertiesV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY);

							$route = $group->post('', [$this->connectorPropertiesV1Controller, 'create']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY);

							$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
								$this->connectorPropertiesV1Controller,
								'update',
							]);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY);

							$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
								$this->connectorPropertiesV1Controller,
								'delete',
							]);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->connectorPropertiesV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP);

							$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
								Routing\RouteCollector $group,
							): void {
								/**
								 * STATE
								 */
								$route = $group->get('/state', [$this->connectorPropertyStateV1Controller, 'read']);
								$route->setName(self::ROUTE_NAME_CONNECTOR_PROPERTY_STATE);
							});
						});

						/**
						 * CONNECTOR CONTROLS
						 */
						$group->group('/controls', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->connectorControlsV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_CONTROLS);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
								$this->connectorControlsV1Controller,
								'read',
							]);
							$route->setName(self::ROUTE_NAME_CONNECTOR_CONTROL);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->connectorControlsV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP);
						});

						/**
						 * DEVICES
						 */
						$group->group('/devices', function (Routing\RouteCollector $group): void {
							$route = $group->get('', [$this->devicesV1Controller, 'index']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICES);

							$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->devicesV1Controller, 'read']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE);

							$route = $group->post('', [$this->devicesV1Controller, 'create']);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE);

							$route = $group->patch(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->devicesV1Controller, 'update'],
							);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE);

							$route = $group->delete(
								'/{' . self::URL_ITEM_ID . '}',
								[$this->devicesV1Controller, 'delete'],
							);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE);

							$route = $group->get(
								'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
								[
									$this->devicesV1Controller,
									'readRelationship',
								],
							);
							$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP);

							$group->group(
								'/{' . self::URL_DEVICE_ID . '}',
								function (Routing\RouteCollector $group): void {
									/**
									 * DEVICE PROPERTIES
									 */
									$group->group(
										'/properties',
										function (Routing\RouteCollector $group): void {
											$route = $group->get('', [$this->devicePropertiesV1Controller, 'index']);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES);

											$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
												$this->devicePropertiesV1Controller,
												'read',
											]);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

											$route = $group->post('', [$this->devicePropertiesV1Controller, 'create']);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

											$route = $group->patch('/{' . self::URL_ITEM_ID . '}', [
												$this->devicePropertiesV1Controller,
												'update',
											]);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

											$route = $group->delete('/{' . self::URL_ITEM_ID . '}', [
												$this->devicePropertiesV1Controller,
												'delete',
											]);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY);

											$route = $group->get(
												'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
												[
													$this->devicePropertiesV1Controller,
													'readRelationship',
												],
											);
											$route->setName(
												self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP,
											);

											$group->group('/{' . self::URL_PROPERTY_ID . '}', function (
												Routing\RouteCollector $group,
											): void {
												/**
												 * CHILDREN
												 */
												$route = $group->get('/children', [
													$this->devicePropertyChildrenV1Controller,
													'index',
												]);
												$route->setName(
													self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN,
												);

												/**
													 * STATE
													 */
												$route = $group->get(
													'/state',
													[$this->devicePropertyStateV1Controller, 'read'],
												);
												$route->setName(
													self::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE,
												);
											});
										},
									);

									/**
									 * DEVICE CONTROLS
									 */
									$group->group(
										'/controls',
										function (Routing\RouteCollector $group): void {
											$route = $group->get('', [$this->deviceControlsV1Controller, 'index']);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS);

											$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
												$this->deviceControlsV1Controller,
												'read',
											]);
											$route->setName(self::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL);

											$route = $group->get(
												'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
												[
													$this->deviceControlsV1Controller,
													'readRelationship',
												],
											);
											$route->setName(
												self::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP,
											);
										},
									);
								},
							);
						});
					},
				);
			});
		});
	}

}
