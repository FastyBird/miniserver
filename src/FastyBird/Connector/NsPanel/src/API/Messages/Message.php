<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages;

use Orisai\ObjectMapper;

/**
 * NS Panel base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	public function toJson(): object;

}
