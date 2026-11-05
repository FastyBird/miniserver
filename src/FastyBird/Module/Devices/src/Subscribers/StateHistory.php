<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Core\EventLoop\Events\EventLoopStarted;
use FastyBird\Core\EventLoop\Events\EventLoopStopped;
use FastyBird\Core\EventLoop\Events\EventLoopStopping;
use FastyBird\Core\Persistence\Events\DbTransactionFinished;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use Override;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * Feeds the property value history from the property state events, and flushes it on the event loop's
 * life cycle
 *
 * The timer starts with the loop, writes what the recorder buffered every `history.flushInterval`
 * seconds and ends with the loop. A process whose loop does not run never starts one, and the recorder
 * writes each change at once.
 */
final class StateHistory implements EventDispatcher\EventSubscriberInterface
{

	private EventLoop\TimerInterface|null $timer = null;

	public function __construct(
		private readonly Models\History\Recorder $recorder,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly int $flushInterval,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\ConnectorPropertyStateEntityCreated::class => 'stateCreated',
			Events\ConnectorPropertyStateEntityUpdated::class => 'stateUpdated',
			Events\DevicePropertyStateEntityCreated::class => 'stateCreated',
			Events\DevicePropertyStateEntityUpdated::class => 'stateUpdated',
			Events\ChannelPropertyStateEntityCreated::class => 'stateCreated',
			Events\ChannelPropertyStateEntityUpdated::class => 'stateUpdated',
			EventLoopStarted::class => 'loopStarted',
			EventLoopStopping::class => 'loopStopped',
			EventLoopStopped::class => 'loopStopped',
			DbTransactionFinished::class => 'transactionFinished',
		];
	}

	public function stateCreated(
		Events\ConnectorPropertyStateEntityCreated|Events\DevicePropertyStateEntityCreated|Events\ChannelPropertyStateEntityCreated $event,
	): void
	{
		$this->recorder->record($event->getProperty(), $event->getRead());
	}

	public function stateUpdated(
		Events\ConnectorPropertyStateEntityUpdated|Events\DevicePropertyStateEntityUpdated|Events\ChannelPropertyStateEntityUpdated $event,
	): void
	{
		$this->recorder->record($event->getProperty(), $event->getRead());
	}

	/**
	 * A transaction that Database::transaction() ran is over: what the recorder held back inside one is written
	 */
	public function transactionFinished(): void
	{
		$this->recorder->retryDeferred();
	}

	public function loopStarted(): void
	{
		if ($this->timer !== null) {
			return;
		}

		$this->timer = $this->eventLoop->addPeriodicTimer(
			$this->flushInterval,
			function (): void {
				$this->recorder->flush();
			},
		);
	}

	public function loopStopped(): void
	{
		if ($this->timer !== null) {
			$this->eventLoop->cancelTimer($this->timer);

			$this->timer = null;
		}

		$this->recorder->drain();
	}

}
