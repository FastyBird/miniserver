<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Zigbee2Mqtt connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: Clients\Client collapsed into Clients\Mqtt (census T2), so Clients\ClientFactory::create()
 *   returns it.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Client' => 'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt',
	],
	'removed' => [
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\Client' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttBuilder' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttParser' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Flow' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Mqtt' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Subscribers\\Bridge' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Subscribers\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\DI\\Zigbee2MqttExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Documents\\Devices\\Bridge' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Documents\\Devices\\SubDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\Logic' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\MissingValue' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\ParseMessage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Models\\StateRepository' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindBridgeDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Configuration\\FindSubDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindBridgeDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries\\Entities\\FindSubDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeDevices' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\ChannelProperty',
				'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeEvent' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeGroups' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeInfo' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreBridgeLog' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\StoreDeviceState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Consumers\\WriteSubDeviceChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
