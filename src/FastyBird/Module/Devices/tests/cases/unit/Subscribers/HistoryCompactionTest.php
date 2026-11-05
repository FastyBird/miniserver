<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Subscribers;

use Closure;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React\EventLoop;
use Throwable;

/**
 * The exchange process compacts the property value history on its event loop (#624)
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class HistoryCompactionTest extends Tests\Cases\Unit\HistoryTestCase
{

	public function testSubscribesToTheExchangeStartup(): void
	{
		self::assertSame(
			[Events\ExchangeStartup::class => 'exchangeStarted'],
			Subscribers\HistoryCompaction::getSubscribedEvents(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testCompactsOnceSoonAndThenEveryFiveMinutes(): void
	{
		$this->insertSample(self::NOW - 1_800, 10.0);

		$first = null;
		$periodic = null;

		$loop = $this->createMock(EventLoop\LoopInterface::class);
		$loop
			->expects(self::once())
			->method('addTimer')
			->with(30)
			->willReturnCallback(
				static function (int|float $interval, callable $listener) use (&$first): EventLoop\TimerInterface {
					$first = $listener;

					return self::createStub(EventLoop\TimerInterface::class);
				},
			);
		$loop
			->expects(self::once())
			->method('addPeriodicTimer')
			->with(300)
			->willReturnCallback(
				static function (int|float $interval, callable $listener) use (&$periodic): EventLoop\TimerInterface {
					$periodic = $listener;

					return self::createStub(EventLoop\TimerInterface::class);
				},
			);

		$subscriber = new Subscribers\HistoryCompaction($this->compactor(), $loop);

		$subscriber->exchangeStarted();

		self::assertInstanceOf(Closure::class, $first);
		self::assertInstanceOf(Closure::class, $periodic);
		self::assertSame(0, $this->countRollups());

		// The first run
		$first();

		$count = $this->countRollups();

		self::assertGreaterThan(0, $count);

		// A run of the periodic timer finds it all done and changes nothing
		$periodic();

		self::assertSame($count, $this->countRollups());
	}

}
