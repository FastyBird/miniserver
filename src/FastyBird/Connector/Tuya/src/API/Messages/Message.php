<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages;

use Orisai\ObjectMapper;

/**
 * Tuya base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
