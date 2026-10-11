<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients\Messages;

/**
 * Modbus base request interface
 */
interface Message
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
