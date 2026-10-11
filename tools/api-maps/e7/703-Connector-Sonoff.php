<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Sonoff connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: the traits API\Messages\Uiid\TDevice, TSwitch and TSwitches to their census T1 role names.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TDevice' => 'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\MapsDeviceStates',
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TSwitch' => 'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\MapsSwitchStates',
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TSwitches' => 'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\MapsMultiSwitchStates',
	],
	'removed' => [
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__call()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__get()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__isset()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__set()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi::__unset()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__call()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__get()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__isset()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__set()',
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs::__unset()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__call()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__get()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__isset()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__set()',
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::__unset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__call()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__get()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__isset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__set()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto::__unset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__call()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__get()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__isset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__set()',
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::__unset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__call()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__get()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__isset()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__set()',
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway::__unset()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__call()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__get()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__isset()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__set()',
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState::__unset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__call()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__get()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__isset()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__set()',
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory::__unset()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__call()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__get()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__isset()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__set()',
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory::__unset()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Sonoff\\API\\CloudApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\API\\CloudWs' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\API\\LanApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Response\\Lan\\DeviceEventData' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Response\\Lan\\DeviceInfo' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Response\\Lan\\SwitchConfiguration' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Response\\Lan\\SwitchPulse' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Response\\Lan\\SwitchState' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\SwitchConfiguration' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\SwitchPulse' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\SwitchState' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid1' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid102' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid103' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid104' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid107' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid14' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid15' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid16' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid17' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid18' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid19' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid2' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid22' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid24' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid25' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid27' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid28' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid29' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid3' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid30' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid31' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid32' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid33' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid34' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid36' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid4' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid44' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid5' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid52' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid57' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid59' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid6' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid7' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid77' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid78' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid8' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid81' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid82' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid83' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid84' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid9' => [
			'readonly' => true,
		],
		'FastyBird\\Connector\\Sonoff\\API\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\Auto' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\Discovery' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\Gateway' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\DI\\SonoffExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\CloudApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\CloudApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\CloudWsCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\CloudWsCallTimeout' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\CloudWsError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\LanApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\LanApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\NotImplemented' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\NotSupported' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\ParseMessage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Helpers\\Transformer' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\ChannelProperty',
				'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\StoreParametersStates' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Consumers\\WriteDevicePropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Services\\HttpClientFactory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Services\\MulticastFactory' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Services\\WebSocketClientFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Sonoff\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Sonoff\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
