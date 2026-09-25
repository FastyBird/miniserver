<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Subscribers;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\PushMessages;
use React\EventLoop\LoopInterface;
use function assert;

/**
 * Server start event for push managers
 */
final class OnServerStartHandler
{

	public function __construct(
		private PushMessages\ConsumersRegistry $consumersRegistry,
		private Controllers\IWampApplication $application,
	)
	{
	}

	public function __invoke(LoopInterface $eventLoop): void
	{
		foreach ($this->consumersRegistry->getConsumers() as $consumer) {
			assert($consumer instanceof PushMessages\IConsumer);
			$consumer->connect($eventLoop, $this->application);
		}
	}

}
