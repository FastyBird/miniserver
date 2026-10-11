<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the NsPanel connector's untyped class constants gain a native type. The change
 * list format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest.
 *
 * A client factory's MODE (VERSION in FbMqtt) takes its enum as the type.
 *
 * The base is the manifest of `main` @ 7efb739ab (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Connector\\NsPanel\\API\\LanApi::GATEWAY_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\DeviceFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\NsPanel\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\NsPanel\\Clients\\GatewayFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\NsPanel\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Constants::CHANNEL_IDENTIFIER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Constants::DEFAULT_SERVER_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\NsPanel\\Constants::NS_PANEL_API_VERSION_V1' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Constants::STATE_NAME_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\DI\\NsPanelExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Battery::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Brightness::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\CameraStream::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\ColorRgb::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\ColorTemperature::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Detect::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Fault::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Humidity::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\IlluminationLevel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\MotorCalibration::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\MotorControl::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\MotorReverse::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Percentage::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Power::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Press::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Rssi::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Startup::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Temperature::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Thermostat::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\ThermostatModeDetect::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\ThermostatTargetSetPoint::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Channels\\Toggle::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Devices\\Gateway::HEARTBEAT_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Devices\\Gateway::STATE_READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Devices\\Gateway::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Devices\\SubDevice::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Entities\\Devices\\ThirdPartyDevice::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Router\\Router::URL_DEVICE_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Router\\Router::URL_GATEWAY_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Battery::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Brightness::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\CameraStream::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\ColorRgb::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\ColorTemperature::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Detect::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Fault::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Humidity::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\IlluminationLevel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\MotorCalibration::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\MotorControl::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\MotorReverse::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Percentage::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Power::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Press::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Rssi::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Startup::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Temperature::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Thermostat::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\ThermostatModeDetect::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\ThermostatTargetSetPoint::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Channels\\Toggle::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Devices\\Gateway::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Devices\\SubDevice::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Schemas\\Devices\\ThirdPartyDevice::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::JSON_CONTENT_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Servers\\Http::REQUEST_ATTRIBUTE_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\NsPanel\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
