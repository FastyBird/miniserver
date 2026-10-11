<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Viera connector's untyped class constants gain a native type. The change
 * list format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ 7efb739ab (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Connector\\Viera\\API\\Crypto::SIGNATURE_BYTES_LENGTH' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Viera\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Constants::KEYS_PROPERTIES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\Viera\\Constants::MAX_HDMI_CODE' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Viera\\Constants::MIN_APPLICATION_CODE' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Viera\\Constants::TV_CODE' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Viera\\Constants::TV_IDENTIFIER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\DI\\VieraExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Entities\\Devices\\Device::DEFAULT_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Viera\\Entities\\Devices\\Device::STATE_READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Viera\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Viera\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
