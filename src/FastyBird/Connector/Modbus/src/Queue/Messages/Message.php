<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Modbus base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
