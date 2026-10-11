<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;

/**
 * Zigbee2MQTT base message interface
 */
interface Message extends ObjectMapper\MappedObject
{

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

}
