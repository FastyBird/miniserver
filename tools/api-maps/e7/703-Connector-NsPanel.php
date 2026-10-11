<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the NsPanel connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: Servers\Server collapsed into Servers\Http (census T2), so the kept
 *   Servers\ServerFactory::create() returns it (#708);
 * - census T3: Protocol\Attributes\Attribute::$valid and Protocol\Devices\ThirdPartyDevice::$online/
 *   $provisioned/$corrupted replace their get/set (is/set) pairs.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\NsPanel\\Servers\\Server' => 'FastyBird\\Connector\\NsPanel\\Servers\\Http',
	],
	'removed' => [
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__call()',
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__get()',
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__isset()',
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__set()',
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::__unset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__call()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__get()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__isset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__set()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Device::__unset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__call()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__get()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__isset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__set()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery::__unset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__call()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__get()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__isset()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__set()',
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway::__unset()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__call()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__get()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__isset()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__set()',
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController::__unset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__call()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__get()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__isset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__set()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::__unset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::isValid()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::setValid()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__call()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__get()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__isset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__set()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability::__unset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__call()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__get()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__isset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__set()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration::__unset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__call()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__get()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__isset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__set()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device::__unset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::isCorrupted()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::isOnline()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::isProvisioned()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::setCorrupted()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::setOnline()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::setProvisioned()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__call()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__get()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__isset()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__set()',
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState::__unset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__call()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__get()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__isset()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__set()',
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::__unset()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__call()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__get()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__isset()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__set()',
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory::__unset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__call()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__get()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__isset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__set()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices::__unset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic::__unset()',
	],
	'added' => [
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::$corrupted' => [
			'visibility' => 'public',
			'set' => 'public',
			'static' => false,
			'readonly' => false,
			'type' => 'bool',
			'hasDefault' => true,
			'default' => 'false',
			'promoted' => false,
			'virtual' => false,
			'final' => false,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::$online' => [
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
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice::$provisioned' => [
			'visibility' => 'public',
			'set' => 'public',
			'static' => false,
			'readonly' => false,
			'type' => 'bool',
			'hasDefault' => true,
			'default' => 'false',
			'promoted' => false,
			'virtual' => false,
			'final' => false,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
	],
	'changed' => [
		'FastyBird\\Connector\\NsPanel\\API\\LanApi' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\API\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\Device' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\Discovery' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\Gateway' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Discover' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Controllers\\BaseController' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\DI\\NsPanelExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Battery' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Brightness' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\CameraStream' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\ColorRgb' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\ColorTemperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Detect' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Fault' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Humidity' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\IlluminationLevel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\MotorCalibration' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\MotorControl' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\MotorReverse' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Percentage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Power' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Press' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Rssi' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Startup' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Temperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\ThermostatModeDetect' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\ThermostatTargetSetPoint' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Channels\\Toggle' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Devices\\Gateway' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Devices\\SubDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Documents\\Devices\\ThirdPartyDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Events\\Request' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Events\\Response' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\LanApiCall' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\LanApiError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\MissingValue' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Exceptions\\ServerRequestError' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Attributes\\Attribute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Capabilities' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Capabilities\\Capability' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Capabilities\\Group' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Categories' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Categories\\Category' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Mapping\\Configurations\\Configuration' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute::$valid' => [
			'visibility' => 'public',
			'set' => 'public',
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Attribute' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Battery' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\BatteryFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Brightness' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\BrightnessFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorBlue' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorBlueFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorGreen' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorGreenFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorRed' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorRedFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorTemperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ColorTemperatureFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Detected' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\DetectedFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Fault' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\FaultFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Humidity' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\HumidityFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\IlluminationLevel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\IlluminationLevelFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorCalibration' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorCalibrationFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorControl' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorControlFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorReverse' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\MotorReverseFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Percentage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\PercentageFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\PowerState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\PowerStateFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Press' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\PressFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Rssi' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\RssiFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Startup' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\StartupFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\Temperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\TemperatureFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatAdaptiveRecoveryStatus' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatAdaptiveRecoveryStatusFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatMode' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatModeDetection' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatModeDetectionFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatModeFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatTargetSetPoint' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ThermostatTargetSetPointFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ToggleState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Attributes\\ToggleStateFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Battery' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\BatteryFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Brightness' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\BrightnessFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\CameraStream' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\CameraStreamFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Capability' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ColorRgb' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ColorRgbFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ColorTemperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ColorTemperatureFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Detect' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\DetectFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Fault' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\FaultFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Humidity' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\HumidityFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\IlluminationLevel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\IlluminationLevelFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorCalibration' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorCalibrationFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorControl' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorControlFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorReverse' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\MotorReverseFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Percentage' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\PercentageFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Power' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\PowerFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Press' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\PressFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Rssi' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\RssiFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Startup' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\StartupFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Temperature' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\TemperatureFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ThermostatFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ThermostatModeDetect' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ThermostatModeDetectFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ThermostatTargetSetPoint' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ThermostatTargetSetPointFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\Toggle' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Capabilities\\ToggleFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\Configuration' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\MappingMode' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\MappingModeFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\RangeMax' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\RangeMaxFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\RangeMin' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\RangeMinFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\StreamUrl' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\StreamUrlFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionLowerSetPointScale' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionLowerSetPointScaleFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionLowerSetPointValue' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionLowerSetPointValueFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionModes' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionModesFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionUpperSetPointScale' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionUpperSetPointScaleFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionUpperSetPointValue' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\SupportedDetectionUpperSetPointValueFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureIncrement' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureIncrementFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureMax' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureMaxFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureMin' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureMinFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureScale' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\TemperatureScaleFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\ThermostatSupportedModes' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Configurations\\ThermostatSupportedModesFactory' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\Device' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\SubDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\SubDeviceFactory' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDevice' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Devices\\ThirdPartyDeviceFactory' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Driver' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Protocol\\Loader' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindGatewayDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindSubDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Configuration\\FindThirdPartyDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindGatewayDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindSubDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queries\\Entities\\FindThirdPartyDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreDeviceState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreSubDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\StoreThirdPartyDevice' => [
			'readonly' => true,
			'traits' => [
				'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\DeviceProperty',
			],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteSubDeviceState' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Consumers\\WriteThirdPartyDeviceState' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Router\\Router' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Servers\\Http' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Services\\HttpClientFactory' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Devices' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\NsPanel\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\NsPanel\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
