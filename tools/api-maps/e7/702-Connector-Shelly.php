<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Shelly connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::AUTHORIZATION_BASIC' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\API\\HttpApi::AUTHORIZATION_DIGEST' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Clients\\CloudFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Shelly\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Shelly\\Clients\\LocalFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Shelly\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Shelly\\Clients\\MqttFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Shelly\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::COAP_ADDRESS' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::COAP_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_CLOSED' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_CONNECTED' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_DISCONNECTED' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_ERROR' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_LOST' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::EVENT_MESSAGE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::STATE_READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Shelly\\Constants::VALUE_NOT_AVAILABLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Constants::WRITE_DEBOUNCE_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Shelly\\DI\\ShellyExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Shelly\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
