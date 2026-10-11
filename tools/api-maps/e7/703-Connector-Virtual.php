<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Virtual connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 */

return [
	'removed' => [
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Virtual\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__call()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__get()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__isset()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__set()',
		'FastyBird\\Connector\\Virtual\\Devices\\Devices::__unset()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__call()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__get()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__isset()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__set()',
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState::__unset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Virtual\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Virtual\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\DI\\VirtualExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Devices\\Devices' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Drivers\\DriversManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Helpers\\Drivers' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queries\\Configuration\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\StoreDevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Consumers\\WriteDevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Virtual\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Virtual\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
