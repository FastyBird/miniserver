<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the FbMqtt connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::API_VERSION_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::CHANNEL_ATTRIBUTE_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::CHANNEL_PARTIAL_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::CHANNEL_PROPERTY_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::CONVENTION_PREFIX_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::DEVICE_ATTRIBUTE_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::DEVICE_FW_INFO_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::DEVICE_HW_INFO_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\API\\V1Validator::DEVICE_PROPERTY_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\ClientFactory::VERSION_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\FbMqttV1::DEVICES_TOPICS' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\FbMqttV1::MQTT_SYSTEM_TOPIC' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Clients\\FbMqttV1Factory::VERSION' => [
			'type' => 'FastyBird\\Connector\\FbMqtt\\Types\\ProtocolVersion',
		],
		'FastyBird\\Connector\\FbMqtt\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::DEFAULT_SERVER_ADDRESS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::DEFAULT_SERVER_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::DEFAULT_SERVER_SECURED_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_API_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_API_QOS_0' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_API_QOS_1' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_API_QOS_2' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_API_V1_VERSION_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::MQTT_TOPIC_DELIMITER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::PAYLOAD_BOOL_FALSE_VALUE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::PAYLOAD_BOOL_TRUE_VALUE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::VALUE_NOT_SET' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants::WRITE_DEBOUNCE_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\FbMqtt\\DI\\FbMqttExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::CHANNELS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::EXTENSIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\Attribute::STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ChannelAttribute::ALLOWED_ATTRIBUTES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\DeviceAttribute::ALLOWED_ATTRIBUTES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::ALLOWED_PARAMETERS' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::MAC_ADDRESS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\ExtensionAttribute::VERSION' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::ALLOWED_ATTRIBUTES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::DATA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::FORMAT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::FORMAT_ALLOWED_PAYLOADS' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::QUERYABLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::SETTABLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Queue\\Messages\\PropertyAttribute::UNIT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\FbMqtt\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
