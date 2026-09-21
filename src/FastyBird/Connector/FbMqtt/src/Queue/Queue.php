<?php declare(strict_types = 1);

/**
 * Queue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           03.12.23
 */

namespace FastyBird\Connector\FbMqtt\Queue;

use FastyBird\Connector\FbMqtt;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use SplQueue;

/**
 * Clients message consumer proxy
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Queue
{

	use Nette\SmartObject;

	/** @var SplQueue<Messages\Message> */
	private SplQueue $queue;

	public function __construct(private readonly FbMqtt\Logger $logger)
	{
		$this->queue = new SplQueue();
	}

	public function append(Messages\Message $message): void
	{
		$this->queue->enqueue($message);

		$this->logger->debug(
			'Appended new message into messages queue',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
				'type' => 'queue',
				'message' => $message->toArray(),
			],
		);
	}

	public function dequeue(): Messages\Message|false
	{
		$this->queue->rewind();

		if ($this->queue->isEmpty()) {
			return false;
		}

		return $this->queue->dequeue();
	}

	public function isEmpty(): bool
	{
		return $this->queue->isEmpty();
	}

}
