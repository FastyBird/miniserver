<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Virtual connector's untyped class constants gain a native type. The change
 * list format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest.
 *
 * Drivers\DriverFactory::DEVICE_TYPE is ?string with the value null (#709), so that the virtual
 * thermostat addon's string override (typed in #698) is a legal covariant type.
 *
 * The base is the manifest of `main` @ 7efb739ab (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Connector\\Virtual\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Constants::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\DI\\VirtualExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Drivers\\DriverFactory::DEVICE_TYPE' => [
			'type' => '?string',
		],
		'FastyBird\\Connector\\Virtual\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Entities\\Devices\\Device::STATE_PROCESSING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Virtual\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Virtual\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
