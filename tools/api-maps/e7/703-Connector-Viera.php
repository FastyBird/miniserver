<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Viera connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: Clients\Client collapsed into Clients\Television (census T2), so the kept
 *   Clients\ClientFactory::create() returns it (#708);
 * - changed `throws`: API\TelevisionApi::getApps() (item 5), and Connector\Connector::execute() declares
 *   what Television::connect() declares, now that the client is typed by the class.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\Viera\\Clients\\Client' => 'FastyBird\\Connector\\Viera\\Clients\\Television',
	],
	'removed' => [
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__call()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__callStatic()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__get()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__isset()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__set()',
		'FastyBird\\Connector\\Viera\\API\\Crypto::__unset()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__call()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__callStatic()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__get()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__isset()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__set()',
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::__unset()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\Viera\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__call()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__callStatic()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__get()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__isset()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__set()',
		'FastyBird\\Connector\\Viera\\Clients\\Television::__unset()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Viera\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Viera\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__call()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__callStatic()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__get()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__isset()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__set()',
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory::__unset()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__call()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__callStatic()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__get()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__isset()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__set()',
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory::__unset()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__call()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__callStatic()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__get()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__isset()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__set()',
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory::__unset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__call()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__callStatic()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__get()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__isset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__set()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Device::__unset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Viera\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Viera\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\API\\Crypto' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\Application' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\AuthorizePinCode' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\DeviceApps' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\DeviceSpecs' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\DeviceVectorInfo' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\RequestPinCode' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Messages\\Response\\Session' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi::getApps()' => [
			'throws' => [
				'FastyBird\\Connector\\Viera\\Exceptions\\InvalidState',
				'FastyBird\\Connector\\Viera\\Exceptions\\TelevisionApiCall',
				'FastyBird\\Connector\\Viera\\Exceptions\\TelevisionApiError',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\Viera\\API\\TelevisionApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Clients\\Discovery' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Clients\\Television' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Connector\\Connector::execute()' => [
			'throws' => [
				'FastyBird\\Connector\\Viera\\Exceptions\\InvalidArgument',
				'FastyBird\\Connector\\Viera\\Exceptions\\Runtime',
				'FastyBird\\Core\\Documents\\Exceptions\\MalformedInput',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\Logic',
				'FastyBird\\Module\\Devices\\Exceptions\\InvalidArgument',
				'FastyBird\\Module\\Devices\\Exceptions\\InvalidState',
				'TypeError',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\Viera\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Constants' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\DI\\VieraExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\Decrypt' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\Encrypt' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\TelevisionApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Exceptions\\TelevisionApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Configuration\\FindChannelProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Entities\\FindChannelProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDevice' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Services\\HttpClientFactory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Services\\MulticastFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Services\\SocketClientFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Subscribers\\Device' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Viera\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Viera\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
