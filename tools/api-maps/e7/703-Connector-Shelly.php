<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Shelly connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 */

return [
	'removed' => [
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__call()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__get()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__set()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap::__unset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__call()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__get()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__set()',
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi::__unset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__call()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__get()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__set()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi::__unset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__call()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__get()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__set()',
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi::__unset()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__call()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__callStatic()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__get()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__isset()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__set()',
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::__unset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__call()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__get()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__isset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__set()',
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud::__unset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__call()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__get()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__isset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__set()',
		'FastyBird\\Connector\\Shelly\\Clients\\Local::__unset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__call()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__get()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__isset()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__set()',
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt::__unset()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Shelly\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Shelly\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__call()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__get()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__isset()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__set()',
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory::__unset()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__call()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__get()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__isset()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__set()',
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory::__unset()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Shelly\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\Gen1Coap' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\Gen1HttpApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\Gen2HttpApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\Gen2WsApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\HttpApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\API\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Clients\\Cloud' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Clients\\Discovery' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Clients\\Local' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Clients\\Mqtt' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\DI\\ShellyExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\CoapError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\HttpApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\HttpApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\ParseMessage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\WsCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\WsCallTimeout' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Exceptions\\WsError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreDeviceState' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\StoreLocalDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\ChannelProperty',
				'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Services\\HttpClientFactory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Services\\MulticastFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Shelly\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Shelly\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
