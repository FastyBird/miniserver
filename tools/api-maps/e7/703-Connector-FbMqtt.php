<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the FbMqtt connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: the trait Queue\Consumers\TProperty to its census T1 role name; Clients\Client
 *   collapsed into Clients\FbMqttV1 (census T2), so Clients\ClientFactory::create() returns it;
 * - changed `throws`: Connector\Connector::terminate() declares what FbMqttV1::disconnect()
 *   declares, now that the client is typed by the class.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\TProperty' => 'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\HandlesPropertyConfiguration',
		'FastyBird\\Connector\\FbMqtt\\Clients\\Client' => 'FastyBird\\Connector\\FbMqtt\\Clients\\FbMqttV1',
	],
	'removed' => [
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__call()',
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__get()',
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__isset()',
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__set()',
		'FastyBird\\Connector\\FbMqtt\\API\\Client::__unset()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__call()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__get()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__isset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__set()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder::__unset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__call()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__get()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__isset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__set()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser::__unset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__call()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__get()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__isset()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__set()',
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\FbMqtt\\API\\Client' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Builder' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Parser' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\FbMqttV1' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\Flow' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector::terminate()' => [
			'throws' => [
				'FastyBird\\Connector\\FbMqtt\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Module\\Devices\\Exceptions\\InvalidState',
				'TypeError',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\FbMqtt\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\DI\\FbMqttExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Exceptions\\Logic' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Exceptions\\ParseMessage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Helpers\\Payload' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Configuration\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelAttribute' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ChannelProperty' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\HandlesPropertyConfiguration',
			],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceAttribute' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\DeviceProperty' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\HandlesPropertyConfiguration',
			],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\ExtensionAttribute' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1ChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\WriteV1DevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\FbMqtt\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\FbMqtt\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
