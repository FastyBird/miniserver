<?php declare(strict_types = 1);

namespace FastyBird\Core\EventLoop\Subscribers;

use FastyBird\Core\EventLoop;
use FastyBird\Core\EventLoop\Events;
use Override;
use Symfony\Component\EventDispatcher;

/**
 * Event loop events
 */
final readonly class EventLoopLifeCycle implements EventDispatcher\EventSubscriberInterface
{

	public function __construct(private readonly EventLoop\Status $eventLoopStatus)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\EventLoopStarted::class => 'loopStarted',
			Events\EventLoopStopped::class => 'loopStopped',
			Events\EventLoopStopping::class => 'loopStopped',
		];
	}

	public function loopStarted(): void
	{
		$this->eventLoopStatus->setStatus(true);
	}

	public function loopStopped(): void
	{
		$this->eventLoopStatus->setStatus(false);
	}

}
