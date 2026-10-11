<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API;

/**
 * Modbus TCP API interface factory
 */
interface TcpFactory
{

	public function create(): Tcp;

}
