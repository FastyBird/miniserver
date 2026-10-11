<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API;

use FastyBird\Connector\Modbus\Types;

/**
 * Modbus RTU API interface factory
 */
interface RtuFactory
{

	public function create(
		Types\BaudRate $baudRate,
		Types\ByteSize $byteSize,
		Types\StopBits $stopBits,
		Types\Parity $parity,
		string $interface,
	): Rtu;

}
