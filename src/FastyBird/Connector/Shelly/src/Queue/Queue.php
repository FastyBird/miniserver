<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Queue;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Queue\Messages\Message;
use FastyBird\Core\Values\Types\Sources;
use SplQueue;

/**
 * Clients message consumer proxy
 */
final class Queue
{

	/** @var SplQueue<Message> */
	private SplQueue $queue;

	public function __construct(private readonly Shelly\Logger $logger)
	{
		$this->queue = new SplQueue();
	}

	public function append(Messages\Message $message): void
	{
		$this->queue->enqueue($message);

		$this->logger->debug(
			'Appended new message into messages queue',
			[
				'source' => Sources\Connector::SHELLY->value,
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
