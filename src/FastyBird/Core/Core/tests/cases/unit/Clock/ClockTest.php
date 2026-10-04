<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Clock;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use Nette\DI;
use Psr\Clock\ClockInterface;
use ValueError;
use function abs;
use function date_default_timezone_get;
use function microtime;

/**
 * Characterization of Core's two clocks before Epic E5 swaps in PSR-20 (#460 §3.7, E5.9).
 *
 * What a caller gets from getNow() is the contract PSR-20's now() has to keep: both clocks hand
 * out a DateTimeImmutable -- TokenBuilder and TokenValidator assert exactly that -- in the time
 * zone they were given, and the frozen clock's value cannot be changed from outside, even when
 * it was built from a mutable DateTime.
 */
final class ClockTest extends BaseTestCase
{

	private const string FROZEN_AT = '2026-10-03T12:34:56.123456+00:00';

	/**
	 * @throws DateInvalidTimeZoneException
	 */
	public function testSystemClockReturnsAnImmutableNowInItsTimeZone(): void
	{
		$clock = new Clock\SystemClock(new DateTimeZone('Europe/Prague'));

		$before = microtime(true);
		$now = $clock->now();
		$after = microtime(true);

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $now);
		self::assertSame('Europe/Prague', $now->getTimezone()->getName());
		self::assertGreaterThanOrEqual((int) $before, $now->getTimestamp());
		self::assertLessThanOrEqual((int) $after + 1, $now->getTimestamp());
		self::assertNotSame($now, $clock->now());
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 */
	public function testSystemClockDefaultsToThePhpTimeZone(): void
	{
		$now = (new Clock\SystemClock())->now();

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $now);
		self::assertSame(date_default_timezone_get(), $now->getTimezone()->getName());
		self::assertLessThan(5.0, abs((float) $now->format('U.u') - microtime(true)));
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function testFrozenClockFromAnImmutableReturnsAnEqualCopyEveryTime(): void
	{
		$frozenAt = new DateTimeImmutable(self::FROZEN_AT);
		$clock = new Clock\FrozenClock($frozenAt, new DateTimeZone('UTC'));

		$first = $clock->now();
		$second = $clock->now();

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $first);
		self::assertSame('2026-10-03T12:34:56.123456+00:00', $first->format('Y-m-d\TH:i:s.uP'));
		self::assertEquals($first, $second);
		self::assertNotSame($first, $second);
		self::assertNotSame($frozenAt, $first);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function testFrozenClockFromAMutableDateTimeStillReturnsAnImmutableThatOutsideChangesCannotMove(): void
	{
		$frozenAt = new DateTime(self::FROZEN_AT);
		$clock = new Clock\FrozenClock($frozenAt, new DateTimeZone('UTC'));

		$frozenAt->modify('+1 day');
		$now = $clock->now();

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $now);
		self::assertSame('2026-10-03T12:34:56.123456+00:00', $now->format('Y-m-d\TH:i:s.uP'));
	}

	/**
	 * What a caller does with the value it was handed cannot move the clock: the value is
	 * immutable, so "mutating" it yields a new instant and leaves the clock where it was.
	 *
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function testModifyingAReturnedValueDoesNotShiftTheFrozenClock(): void
	{
		foreach ([new DateTime(self::FROZEN_AT), new DateTimeImmutable(self::FROZEN_AT)] as $frozenAt) {
			$clock = new Clock\FrozenClock($frozenAt, new DateTimeZone('UTC'));

			$returned = $clock->now();
			// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
			self::assertInstanceOf(DateTimeImmutable::class, $returned);

			$moved = $returned->modify('+1 day');

			self::assertSame('2026-10-04T12:34:56.123456+00:00', $moved->format('Y-m-d\TH:i:s.uP'));
			self::assertSame('2026-10-03T12:34:56.123456+00:00', $returned->format('Y-m-d\TH:i:s.uP'));
			self::assertSame('2026-10-03T12:34:56.123456+00:00', $clock->now()->format('Y-m-d\TH:i:s.uP'));
		}
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function testFrozenClockFromATimestampKeepsTheMicroseconds(): void
	{
		$clock = new Clock\FrozenClock(1_759_494_896.25, new DateTimeZone('Europe/Prague'));

		$now = $clock->now();

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $now);
		self::assertSame(1_759_494_896, $now->getTimestamp());
		self::assertSame('250000', $now->format('u'));
		self::assertSame('Europe/Prague', $now->getTimezone()->getName());
		self::assertSame('2025-10-03T14:34:56.250000+02:00', $now->format('Y-m-d\TH:i:s.uP'));
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	public function testFrozenClockConvertsToTheGivenTimeZoneWithoutMovingTheInstant(): void
	{
		$clock = new Clock\FrozenClock(new DateTimeImmutable(self::FROZEN_AT), new DateTimeZone('America/New_York'));

		$now = $clock->now();

		self::assertSame('America/New_York', $now->getTimezone()->getName());
		self::assertSame((new DateTimeImmutable(self::FROZEN_AT))->getTimestamp(), $now->getTimestamp());
		self::assertSame('2026-10-03T08:34:56.123456-04:00', $now->format('Y-m-d\TH:i:s.uP'));
	}

	/**
	 * The clock the container autowires. Core's test configuration freezes it
	 * (fbCore > clock > frozen), so the frozen clock is the one every service gets; the system
	 * clock is still registered beside it, but not for autowiring.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testTheContainerAutowiresTheConfiguredClock(): void
	{
		$clock = $this->container->getByType(ClockInterface::class);

		self::assertInstanceOf(Clock\FrozenClock::class, $clock);
		self::assertSame(
			['fbCore.clock.frozen', 'fbCore.clock.system'],
			$this->container->findByType(ClockInterface::class),
		);
		self::assertInstanceOf(Clock\SystemClock::class, $this->container->getService('fbCore.clock.system'));

		$now = $clock->now();

		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; this pins it at run time)
		self::assertInstanceOf(DateTimeImmutable::class, $now);
		self::assertSame('2020-04-01T12:00:00.000000+00:00', $now->format('Y-m-d\TH:i:s.uP'));
		self::assertSame('UTC', $now->getTimezone()->getName());
	}

}
