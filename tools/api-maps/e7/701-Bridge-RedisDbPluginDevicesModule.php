<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/RedisDbPluginDevicesModule. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type, final classes and Nette\SmartObject removed from 12 classes.
 * States\Property stays open (its three subclasses extend it).
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository::__unset()',
	],
	'changed' => [
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\DI\\RedisDbPluginDevicesModuleExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\DI\\RedisDbPluginDevicesModuleExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\States\\ChannelProperty' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\States\\ConnectorProperty' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\States\\DeviceProperty' => [
			'final' => true,
		],
	],
];
