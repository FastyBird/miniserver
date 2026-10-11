<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Viera base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
