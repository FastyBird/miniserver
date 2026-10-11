<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/VirtualThermostatAddonHomeKitConnector. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (the SCHEMA_TYPE and TYPE overrides of the HomeKit parents included,
 * D8), final and readonly classes, Nette\SmartObject removed from 2 classes. The Doctrine entities
 * stay open.
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__call()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__callStatic()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__get()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__isset()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__set()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder::__unset()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__call()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__get()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__set()',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1::__unset()',
	],
	'changed' => [
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Builders\\Builder' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Commands\\Build::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Commands\\Build' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants::MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants::ROUTE_NAME_BRIDGE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants::ROUTE_NAME_BRIDGES' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Controllers\\BridgesV1' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\DI\\VirtualThermostatAddonHomeKitConnectorExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\DI\\VirtualThermostatAddonHomeKitConnectorExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Documents\\Channels\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Documents\\Devices\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Entities\\Channels\\Thermostat::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Entities\\Devices\\Thermostat::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Hydrators\\Channels\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Hydrators\\Devices\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Protocol\\Accessories\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Protocol\\Accessories\\ThermostatFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Protocol\\Services\\ThermostatFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Queries\\Configuration\\FindThermostatChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Queries\\Configuration\\FindThermostatDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Queries\\Entities\\FindThermostatChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Queries\\Entities\\FindThermostatDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Schemas\\Channels\\Thermostat::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Schemas\\Devices\\Thermostat::SCHEMA_TYPE' => [
			'type' => 'string',
		],
	],
];
