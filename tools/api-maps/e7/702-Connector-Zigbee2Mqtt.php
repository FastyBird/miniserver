<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Zigbee2Mqtt connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::BRIDGE_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::BRIDGE_REQUEST_RESPONSE_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::DEVICE_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::DEVICE_WITH_ACTION_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\API\\MqttValidator::TOPIC_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\Discovery::DISCOVERY_TOPIC' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Clients\\MqttFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Zigbee2Mqtt\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::BRIDGE_TOPIC' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::CHANNEL_IDENTIFIER_REGEX' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::CHANNEL_SPECIAL_IDENTIFIER_REGEX' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_CLOSE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_CONNECT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_DISCONNECT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_ERROR' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_MESSAGE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_PUBLISH' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_SUBSCRIBE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_UNSUBSCRIBE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::EVENT_WARNING' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::IEEE_ADDRESS_REGEX' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::MQTT_API_QOS_0' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::MQTT_API_QOS_1' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::MQTT_API_QOS_2' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::MQTT_TOPIC_DELIMITER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants::VALUE_NOT_AVAILABLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\DI\\Zigbee2MqttExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Connectors\\Connector::DEFAULT_SERVER_ADDRESS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Connectors\\Connector::DEFAULT_SERVER_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Connectors\\Connector::DEFAULT_SERVER_SECURED_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Devices\\Bridge::BASE_TOPIC' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Devices\\Bridge::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities\\Devices\\SubDevice::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Schemas\\Devices\\Bridge::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Schemas\\Devices\\SubDevice::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
