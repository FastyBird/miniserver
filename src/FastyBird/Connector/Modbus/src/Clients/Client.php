<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients;

/**
 * Modbus device client interface
 */
interface Client
{

	public const int MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST = 124;

	public const int MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST = 2_048;

	/**
	 * Create servers/clients
	 */
	public function connect(): void;

	/**
	 * Destroy servers/clients
	 */
	public function disconnect(): void;

}
