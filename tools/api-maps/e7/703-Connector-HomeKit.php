<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the HomeKit connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - census T3: Protocol\Accessories\Accessory::$aid, Protocol\Characteristics\Characteristic::$valid
 *   and Protocol\Services\Service::$primary/$hidden replace their get/set (is/set) pairs;
 * - changed `throws` (item 5): AccessoriesController::identify() and PairingController::pairings().
 */

return [
	'removed' => [
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__call()',
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__get()',
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__isset()',
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__set()',
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber::__unset()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__call()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__get()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__isset()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__set()',
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController::__unset()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__call()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__get()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__isset()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__set()',
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager::__unset()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__call()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__get()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__isset()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__set()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager::__unset()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__call()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__get()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__isset()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__set()',
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::getAid()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::setAid()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::isValid()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::setValid()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::isHidden()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::isPrimary()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::setHidden()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::setPrimary()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp::__unset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__call()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__get()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__isset()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__set()',
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState::__unset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__call()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__get()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__isset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__set()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::__unset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__call()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__get()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__isset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__set()',
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns::__unset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__call()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__get()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__isset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__set()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection::__unset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__call()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__get()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__isset()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__set()',
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer::__unset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__call()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__get()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__isset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__set()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities::__unset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__call()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__callStatic()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__get()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__isset()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__set()',
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System::__unset()',
	],
	'added' => [
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic::$valid' => [
			'visibility' => 'public',
			'set' => 'public',
			'static' => false,
			'readonly' => false,
			'type' => 'bool',
			'hasDefault' => true,
			'default' => 'true',
			'promoted' => false,
			'virtual' => false,
			'final' => false,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::$hidden' => [
			'visibility' => 'public',
			'set' => 'private',
			'static' => false,
			'readonly' => false,
			'type' => 'bool',
			'hasDefault' => false,
			'default' => null,
			'promoted' => true,
			'virtual' => false,
			'final' => true,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::$primary' => [
			'visibility' => 'public',
			'set' => 'private',
			'static' => false,
			'readonly' => false,
			'type' => 'bool',
			'hasDefault' => false,
			'default' => null,
			'promoted' => true,
			'virtual' => false,
			'final' => true,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
	],
	'changed' => [
		'FastyBird\\Connector\\HomeKit\\Clients\\Subscriber' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Controllers\\AccessoriesController::identify()' => [
			'throws' => [
				'FastyBird\\Connector\\HomeKit\\Exceptions\\HapRequestError',
				'FastyBird\\Connector\\HomeKit\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Module\\Devices\\Exceptions\\InvalidState',
				'InvalidArgumentException',
				'TypeError',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\HomeKit\\Controllers\\BaseController' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Controllers\\PairingController::pairings()' => [
			'throws' => [
				'Exception',
				'FastyBird\\Connector\\HomeKit\\Exceptions\\InvalidArgument',
				'FastyBird\\Connector\\HomeKit\\Exceptions\\InvalidState',
				'FastyBird\\Connector\\HomeKit\\Exceptions\\Runtime',
				'InvalidArgumentException',
				'RuntimeException',
				'TypeError',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\HomeKit\\DI\\HomeKitExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Documents\\Channels\\Battery' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Documents\\Channels\\Generic' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Documents\\Channels\\LightBulb' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Events\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Events\\Response' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Exceptions\\HapRequestError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Exceptions\\UnsupportedType' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Helpers\\IidManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Hydrators\\Channels\\Battery' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Hydrators\\Channels\\Generic' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Hydrators\\Channels\\LightBulb' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Models\\Entities\\Clients\\ClientsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory::$aid' => [
			'visibility' => 'public',
			'set' => 'public',
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Accessory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\Bridge' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\BridgeFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Accessories\\GenericFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Characteristic' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\DynamicProperty' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\DynamicPropertyFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\Generic' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\GenericFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\MappedProperty' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\MappedPropertyFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\VariableProperty' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Characteristics\\VariablePropertyFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Driver' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Loader' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\BatteryFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\GenericFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\LightBulbFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Srp' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Tlv' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Configuration\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Entities\\FindClients' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\StoreDevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Consumers\\WriteDevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Router\\Router' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\Http' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\Mdns' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureConnection' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\SecureServer' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Entities' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Subscribers\\System' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\HomeKit\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\HomeKit\\Writers\\Exchange' => [
			'final' => true,
		],
	],
];
