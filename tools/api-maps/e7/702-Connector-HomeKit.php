<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the HomeKit connector's untyped class constants gain a native type. The change
 * list format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ 7efb739ab (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Connector\\HomeKit\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::BASE_UUID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::DEFAULT_BRIDGE_MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::DEFAULT_CONFIG_VERSION' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::DEFAULT_DEVICE_MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::DEFAULT_MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::DEFAULT_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::HAP_PROTOCOL_SHORT_VERSION' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::HAP_PROTOCOL_VERSION' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::MAX_CONFIG_VERSION' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::PACKAGE_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::SERVICE_IDENTIFIER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::STANDALONE_AID' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\HomeKit\\Constants::VERSION_REGEXP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\DI\\HomeKitExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Channels\\Battery::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Channels\\Channel::SERVICE_IDENTIFIER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Channels\\Generic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Channels\\LightBulb::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Protocol\\Services\\Service::HAP_PROTOCOL_INFORMATION_SERVICE_UUID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Schemas\\Channels\\Battery::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Schemas\\Channels\\Generic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Schemas\\Channels\\LightBulb::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::JSON_CONTENT_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::PAIRING_CONTENT_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Servers\\Http::REQUEST_ATTRIBUTE_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\HomeKit\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
