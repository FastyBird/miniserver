<?php declare(strict_types = 1);

namespace FastyBird\Core\Subscribers\WsServer;

use FastyBird\Core\Controllers\WebSockets\IWampApplication;
use FastyBird\Core\Messaging\WebSockets\PushMessages;
use React\EventLoop\LoopInterface;
use function assert;

/**
 * Server start event for push managers
 */
final class OnServerStartHandler
{

	public function __construct(
		private PushMessages\ConsumersRegistry $consumersRegistry,
		private IWampApplication $application,
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
