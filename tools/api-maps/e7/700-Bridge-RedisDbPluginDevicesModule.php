<?php declare(strict_types = 1);

/**
 * #700 (E7.6, #462): the consumer sweep into Bridge/RedisDbPluginDevicesModule. Its state classes implement the
 * module's renamed state-store contracts (census T1), so only their `interfaces` lists change.
 * The format is documented in tools/api-surface.php; the base is the manifest of `main` @ 746772d5e.
 */

return [
	'changed' => [
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Async\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ChannelPropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Async\\Reader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Async\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\ConnectorPropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Async\\Reader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Async\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\Async\\DevicePropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Async\\Reader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ChannelPropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Reader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\ConnectorPropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Reader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Writer',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginDevicesModule\\Models\\States\\DevicePropertiesRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Reader',
			],
		],
	],
];
