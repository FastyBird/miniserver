<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Store bridge groups list message consumer
 */
final readonly class StoreBridgeGroups implements Queue\Consumer
{

	public function __construct(private Zigbee2Mqtt\Logger $logger)
	{
	}

	#[Override]
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
