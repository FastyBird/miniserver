<?php declare(strict_types = 1);

/**
 * #541: normalize every FastyBird\Connector\* namespace that still carries a bare import
 * colliding with a same-kind sibling's short name, per `tools/naming-baseline.txt` at
 * `ce86cbe20` -- 232 entries across the 9 connectors (Zigbee2Mqtt 36, HomeKit 30, NsPanel 29,
 * Sonoff 25, Modbus 25, Shelly 23, Viera 22, Tuya 22, FbMqtt 20), reducing to the 36 distinct
 * FQCNs below, derived mechanically from the baseline (every entry is bare, none carries an
 * illegal alias).
 *
 * `classes`, `namespaces` and `files` are empty -- none of these move, they are only
 * (re-)aliased wherever `make naming`'s check 4 requires it. Normalization is repository-wide
 * per namespace (#541's plan comment 3): a bridge or addon file importing one of these
 * namespaces is a candidate too, not just files under src/FastyBird/Connector/. A connector's
 * own OWN namespace pair colliding with ANOTHER connector's, or with a package not in this map,
 * is still handled -- the tool aliases whichever side is bare and needs it, symmetrically with
 * whatever sibling is already present in the same file.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [
		'FastyBird\\Connector\\FbMqtt\\Documents',
		'FastyBird\\Connector\\FbMqtt\\Entities',
		'FastyBird\\Connector\\FbMqtt\\Queries',
		'FastyBird\\Connector\\HomeKit\\Documents',
		'FastyBird\\Connector\\HomeKit\\Entities',
		'FastyBird\\Connector\\HomeKit\\Models',
		'FastyBird\\Connector\\HomeKit\\Queries',
		'FastyBird\\Connector\\HomeKit\\Types',
		'FastyBird\\Connector\\Modbus\\Documents',
		'FastyBird\\Connector\\Modbus\\Entities',
		'FastyBird\\Connector\\Modbus\\Queries',
		'FastyBird\\Connector\\NsPanel\\Documents',
		'FastyBird\\Connector\\NsPanel\\Entities',
		'FastyBird\\Connector\\NsPanel\\Queries',
		'FastyBird\\Connector\\Shelly\\Documents',
		'FastyBird\\Connector\\Shelly\\Entities',
		'FastyBird\\Connector\\Shelly\\Queries',
		'FastyBird\\Connector\\Shelly\\Types',
		'FastyBird\\Connector\\Sonoff\\Documents',
		'FastyBird\\Connector\\Sonoff\\Entities',
		'FastyBird\\Connector\\Sonoff\\Queries',
		'FastyBird\\Connector\\Sonoff\\Types',
		'FastyBird\\Connector\\Tuya\\Documents',
		'FastyBird\\Connector\\Tuya\\Entities',
		'FastyBird\\Connector\\Tuya\\Queries',
		'FastyBird\\Connector\\Tuya\\Types',
		'FastyBird\\Connector\\Viera\\Documents',
		'FastyBird\\Connector\\Viera\\Entities',
		'FastyBird\\Connector\\Viera\\Queries',
		'FastyBird\\Connector\\Viera\\Types',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Documents',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Entities',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Exceptions',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Models',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Queries',
		'FastyBird\\Connector\\Zigbee2Mqtt\\Types',
	],
	'namespaces' => [],
	'files' => [],
];
