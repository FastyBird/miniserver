<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Virtual base data message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
