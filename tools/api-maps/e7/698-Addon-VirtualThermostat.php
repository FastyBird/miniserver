<?php declare(strict_types = 1);

/**
 * E7.4 (#698), Addon/VirtualThermostat. The change list format is documented in
 * tools/api-surface.php.
 *
 *   - 26 public constants gain a type. 17 are string: the 2 NAME and 6 TYPE constants, the 6
 *     SCHEMA_TYPE constants, Constants::MANUFACTURER and PRESET_CHANNEL_PATTERN, and
 *     Drivers\ThermostatFactory::DEVICE_TYPE. 9 are float: the temperature and tolerance
 *     constants of Entities\Devices\Device. DEVICE_TYPE overrides the still untyped
 *     Connector\Virtual\Drivers\DriverFactory::DEVICE_TYPE (#462 D8, #709): the child is typed
 *     first, the parent follows in the Connector PR.
 *   - 24 classes become final (census T4): Commands\Install, the DI extension, Drivers\Thermostat,
 *     the three exceptions, the six Documents and the twelve Queries\*\Find* classes.
 *   - Nothing is renamed or removed: census T1 and T3 are "None", T2 keeps
 *     Drivers\ThermostatFactory (K3) and Exceptions\Exception (D6 marker). The package has no
 *     SmartObject user (T6), no locator (T8) and no missingType.checkedException entry.
 */

return [
	'changed' => [
		'FastyBird\\Addon\\VirtualThermostat\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Constants::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Constants::PRESET_CHANNEL_PATTERN' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\DI\\VirtualThermostatExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\DI\\VirtualThermostatExtension' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Channels\\Actors' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Channels\\Configuration' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Channels\\Preset' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Channels\\Sensors' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Channels\\State' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Drivers\\Thermostat' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Drivers\\ThermostatFactory::DEVICE_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Channels\\Actors::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Channels\\Configuration::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Channels\\Preset::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Channels\\Sensors::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Channels\\State::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::COLD_TOLERANCE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::HOT_TOLERANCE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::MAXIMUM_FLOOR_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::MAXIMUM_ROOM_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::MAXIMUM_SET_FLOOR_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::MAXIMUM_SET_ROOM_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::MINIMUM_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::PRECISION' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::TARGET_TEMPERATURE' => [
			'type' => 'float',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindActorChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindConfigurationChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindPresetChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindSensorChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Configuration\\FindStateChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindActorChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindConfigurationChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindPresetChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindSensorChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Queries\\Entities\\FindStateChannels' => [
			'final' => true,
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Channels\\Actors::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Channels\\Configuration::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Channels\\Preset::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Channels\\Sensors::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Channels\\State::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Addon\\VirtualThermostat\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
	],
];
