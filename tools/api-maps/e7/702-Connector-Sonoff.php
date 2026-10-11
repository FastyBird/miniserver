<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Sonoff connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::DEVICE_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::ERROR_DEVICE_ID_INVALID' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::ERROR_INVALID_JSON' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::ERROR_INVALID_PARAMETER' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Sonoff\\API\\LanApi::ERROR_UNAUTHORIZED' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid15::DEVICE_TYPE_HUMIDITY' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid15::DEVICE_TYPE_NORMAL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uiid15::DEVICE_TYPE_TEMPERATURE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::COMMAND_CAPTURE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::COMMAND_CAPTURE_CANCEL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::COMMAND_EDIT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::COMMAND_TRANSMIT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::COMMAND_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::MODE_NATURAL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::MODE_NORMAL' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::MODE_SLEEP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::SPEED_FAST' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::SPEED_MODERATE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::SPEED_SLOW' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::TEMPERATURE_COLD' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::TEMPERATURE_MIDDLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::TEMPERATURE_WARM' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_GET' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_OFF' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_ON' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_START' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_STAY' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\Uuid::VALUE_STOP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\AutoFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Sonoff\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::CMD_HEARTBEAT' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::CMD_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::HANDLER_PROCESSING_INTERVAL' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\ClientProcess::HANDLER_START_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\CloudFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Sonoff\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\GatewayFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Sonoff\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Sonoff\\Clients\\LanFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Sonoff\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Discover::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::CHANNEL_GROUP' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::COOLKIT_APP_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::COOLKIT_APP_SECRET' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::DEFAULT_APP_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::DEFAULT_APP_SECRET' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::HEARTBEAT_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::STATE_READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::VALUE_NOT_AVAILABLE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Constants::WRITE_DEBOUNCE_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Sonoff\\DI\\SonoffExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Sonoff\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
	],
];
