<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Tuya connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 */

return [
	'removed' => [
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__call()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__callStatic()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__get()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__isset()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__set()',
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::__unset()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__call()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__callStatic()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__get()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__isset()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__set()',
		'FastyBird\\Connector\\Tuya\\API\\OpenApi::__unset()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__call()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__callStatic()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__get()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__isset()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__set()',
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::__unset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__call()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__get()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__isset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__set()',
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::__unset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__call()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__get()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__isset()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__set()',
		'FastyBird\\Connector\\Tuya\\Clients\\Local::__unset()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Tuya\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Tuya\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__call()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__get()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__isset()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__set()',
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory::__unset()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__call()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__get()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__isset()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__set()',
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory::__unset()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__call()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__get()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__isset()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__set()',
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory::__unset()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Tuya\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\API\\LocalApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetAccessToken' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDeviceSpecification' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDeviceState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDevicesFactoryInfos' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetDevicesResult' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetRefreshToken' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDeviceChildren' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDeviceDetail' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDeviceFactoryInfos' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDeviceSpecifications' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDeviceState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\GetUserDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\Messages\\Response\\RefreshAccessToken' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\API\\OpenApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\API\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Clients\\Discovery' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Clients\\Local' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\DI\\TuyaExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\LocalApiBusy' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\LocalApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\LocalApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\LocalApiTimeout' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\OpenApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\OpenApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\OpenPulsarError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreChannelPropertyState' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreCloudDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\ChannelProperty',
				'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\StoreLocalDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\ChannelProperty',
				'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Services\\DatagramFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Services\\HttpClientFactory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Services\\SocketClientFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Services\\WebSocketClientFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Tuya\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Tuya\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
