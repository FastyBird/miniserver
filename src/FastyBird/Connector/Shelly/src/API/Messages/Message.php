<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages;

use Orisai\ObjectMapper;

/**
 * Shelly base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
