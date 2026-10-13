<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Support;

use FastyBird\Core\EventLoop as CoreEventLoop;
use Override;
use React\EventLoop as ReactEventLoop;
use function array_filter;
use function array_values;

/**
 * Event loop that records timers and never runs anything
 *
 * The container's loop runs at the end of the PHP process, so a timer left behind by the code under test, a
 * periodic ping or a reply timeout, would keep the test process alive. This one only keeps the timers for the test
 * to look at.
 */
final class InertEventLoop extends CoreEventLoop\Wrapper
{

	/** @var array<ReactEventLoop\TimerInterface> */
	private array $timers = [];

	/**
	 * @return array<ReactEventLoop\TimerInterface>
	 */
	public function getTimers(): array
	{
		return $this->timers;
	}

	#[Override]
	public function addTimer($interval, $callback): ReactEventLoop\TimerInterface
	{
		$timer = $this->createTimer($interval, $callback, false);

		$this->timers[] = $timer;

		return $timer;
	}

	#[Override]
	public function addPeriodicTimer($interval, $callback): ReactEventLoop\TimerInterface
	{
		$timer = $this->createTimer($interval, $callback, true);

		$this->timers[] = $timer;

		return $timer;
	}

	/**
	 * @param callable(): void $callback
	 */
	private function createTimer(float|int $interval, callable $callback, bool $periodic): ReactEventLoop\TimerInterface
	{
		return new class ($interval, $callback, $periodic) implements ReactEventLoop\TimerInterface {

			/**
			 * @param callable(): void $callback
			 */
			public function __construct(
				private readonly float|int $interval,
				private readonly mixed $callback,
				private readonly bool $periodic,
			)
			{
			}

			#[Override]
			public function getInterval(): float|int
			{
				return $this->interval;
			}

			#[Override]
			public function getCallback(): callable
			{
				return $this->callback;
			}

			#[Override]
			public function isPeriodic(): bool
			{
				return $this->periodic;
			}

		};
	}

	#[Override]
	public function cancelTimer(ReactEventLoop\TimerInterface $timer): void
	{
		$this->timers = array_values(
			array_filter($this->timers, static fn (ReactEventLoop\TimerInterface $item): bool => $item !== $timer),
		);
	}

	#[Override]
	public function addSignal($signal, $listener): void
	{
		// Signals are not delivered
	}

	#[Override]
	public function removeSignal($signal, $listener): void
	{
		// Signals are not delivered
	}

	#[Override]
	public function addReadStream($stream, $listener): void
	{
		// Streams are not watched
	}

	#[Override]
	public function removeReadStream($stream): void
	{
		// Streams are not watched
	}

	#[Override]
	public function addWriteStream($stream, $listener): void
	{
		// Streams are not watched
	}

	#[Override]
	public function removeWriteStream($stream): void
	{
		// Streams are not watched
	}

	#[Override]
	public function futureTick($listener): void
	{
		// Nothing runs
	}

	#[Override]
	public function run(): void
	{
		// Nothing runs
	}

	#[Override]
	public function stop(): void
	{
		// Nothing runs
	}

}
