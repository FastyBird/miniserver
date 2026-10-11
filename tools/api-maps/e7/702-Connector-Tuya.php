<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Tuya connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::DP_QUERY_MESSAGE_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::DP_STATE_MESSAGE_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\API\\LocalApi::WIFI_QUERY_MESSAGE_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::WS_MESSAGE_PAYLOAD_DATA_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::WS_MESSAGE_PAYLOAD_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\API\\OpenPulsar::WS_MESSAGE_SCHEMA_FILENAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Clients\\Cloud::REFRESH_SLEEP_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Tuya\\Clients\\CloudFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Tuya\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Tuya\\Clients\\LocalFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Tuya\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::EVENT_CONNECTED' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::EVENT_DISCONNECTED' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::EVENT_ERROR' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::EVENT_LOST' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::EVENT_MESSAGE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\DI\\TuyaExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Entities\\Devices\\Device::HEARTBEAT_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Tuya\\Entities\\Devices\\Device::STATE_READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Tuya\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Tuya\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
