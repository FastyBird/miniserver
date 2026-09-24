<?php declare(strict_types = 1);

/**
 * StoreBridgeGroups.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Core\Values\Types\Sources;
use Nette;

/**
 * Store bridge groups list message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeGroups implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(private readonly Zigbee2Mqtt\Logger $logger)
	{
	}

	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeGroups) {
			return false;
		}

		$this->logger->debug(
			'Consumed bridge groups list message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-bridge-groups-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
