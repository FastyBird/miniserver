<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients;

/**
 * Modbus device client interface
 */
interface Client
{

	/**
	 * Create servers/clients
	 */
	public function connect(): void;

	/**
	 * Destroy servers/clients
	 */
	public function disconnect(): void;

}
