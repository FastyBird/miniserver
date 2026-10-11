<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API\Messages;

/**
 * Modbus base response interface
 */
interface Message
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
