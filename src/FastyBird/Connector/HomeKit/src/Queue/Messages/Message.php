<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * HomeKit base data message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
