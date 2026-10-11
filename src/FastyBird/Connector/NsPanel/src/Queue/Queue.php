<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queue;

use FastyBird\Connector\NsPanel;
use FastyBird\Core\Values\Types\Sources;
use Nette;
use SplQueue;

/**
 * Clients messages queue
 */
final class Queue
{

	use Nette\SmartObject;

	/** @var SplQueue<Messages\Message> */
	private SplQueue $queue;

	public function __construct(private readonly NsPanel\Logger $logger)
	{
		$this->queue = new SplQueue();
	}

	public function append(Messages\Message $message): void
	{
		$this->queue->enqueue($message);

		$this->logger->debug(
			'Appended new message into messages queue',
			[
				'source' => Sources\Connector::NS_PANEL->value,
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
