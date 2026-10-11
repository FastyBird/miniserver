<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus;

/**
 * Connector constants
 */
final class Constants
{

	public const string DEFAULT_RTU_SERIAL_INTERFACE = '/dev/ttyAMA0';

	public const int MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST = 124;

	public const int MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST = 2_048;

	/**
	 * Misc
	 */

	public const float WRITE_DEBOUNCE_DELAY = 2_000.0;

	public const float READING_DELAY = 150.0;

	public const int DEFAULT_TCP_PORT = 502;

}
