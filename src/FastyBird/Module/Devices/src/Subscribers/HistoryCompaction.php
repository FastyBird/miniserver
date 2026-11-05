<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use Override;
use React\EventLoop;
use Symfony\Component\EventDispatcher;

/**
 * Compacts the property value history from the exchange process's event loop
 *
 * Every 5 minutes, and once soon after the exchange starts, so that a restart does not leave the history
 * waiting a whole interval. A run is a handful of queries for each property, a second or so for a hundred of
 * them, and the compactor never throws.
 */
final class HistoryCompaction implements EventDispatcher\EventSubscriberInterface
{

	public const int INTERVAL = 300;

	public const int FIRST_RUN_DELAY = 30;

	public function __construct(
		private readonly Models\History\Compactor $compactor,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	#[Override]
	public static function getSubscribedEvents(): array
	{
		return [
			Events\ExchangeStartup::class => 'exchangeStarted',
		];
	}

	public function exchangeStarted(): void
	{
		$compact = function (): void {
			$this->compactor->compact();
		};

		$this->eventLoop->addTimer(self::FIRST_RUN_DELAY, $compact);
		$this->eventLoop->addPeriodicTimer(self::INTERVAL, $compact);
	}

}
