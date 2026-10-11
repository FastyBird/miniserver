<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages;

use Orisai\ObjectMapper;

/**
 * Sonoff base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
