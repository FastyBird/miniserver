<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue;

use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Queue\Messages\Message;
use FastyBird\Core\Values\Types\Sources;
use Nette;
use SplQueue;

/**
 * Clients message consumer proxy
 */
final class Queue
{

	use Nette\SmartObject;

	/** @var SplQueue<Message> */
	private SplQueue $queue;

	public function __construct(private readonly HomeKit\Logger $logger)
	{
		$this->queue = new SplQueue();
	}

	public function append(Messages\Message $message): void
	{
		$this->queue->enqueue($message);

		$this->logger->debug(
			'Appended new message into messages queue',
			[
				'source' => Sources\Connector::HOMEKIT->value,
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
