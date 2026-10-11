<?php declare(strict_types = 1);

/**
 * #702 (E7.8, #462): the Modbus connector's untyped class constants gain a native type. The change
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
		'FastyBird\\Connector\\Modbus\\Clients\\ClientFactory::MODE_CONSTANT_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Clients\\RtuFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Modbus\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Modbus\\Clients\\TcpFactory::MODE' => [
			'type' => 'FastyBird\\Connector\\Modbus\\Types\\ClientMode',
		],
		'FastyBird\\Connector\\Modbus\\Commands\\Execute::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Constants::DEFAULT_RTU_SERIAL_INTERFACE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Constants::DEFAULT_TCP_PORT' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Modbus\\Constants::MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Modbus\\Constants::MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST' => [
			'type' => 'int',
		],
		'FastyBird\\Connector\\Modbus\\Constants::READING_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Modbus\\Constants::WRITE_DEBOUNCE_DELAY' => [
			'type' => 'float',
		],
		'FastyBird\\Connector\\Modbus\\DI\\ModbusExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Entities\\Channels\\Channel::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Entities\\Connectors\\Connector::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Entities\\Devices\\Device::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusRtu::EXCEPTION_CODES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusTcp::EXCEPTION_CODES' => [
			'type' => 'array',
		],
		'FastyBird\\Connector\\Modbus\\Schemas\\Channels\\Channel::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Schemas\\Connectors\\Connector::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Schemas\\Devices\\Device::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Event::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Exchange::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::NAME' => [
			'type' => 'string',
		],
	],
];
