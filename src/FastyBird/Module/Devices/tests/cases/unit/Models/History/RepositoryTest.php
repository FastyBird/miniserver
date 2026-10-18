<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\History;

use DateTimeImmutable;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_column;
use function array_slice;

/**
 * How a property's history is read back: which tier answers a period, and what the series holds (#624)
 *
 * The container's clock is frozen at NOW. The tiers hold 7 days of samples, 30 of 5-minute rollups and 365 of
 * hours unless a test says otherwise.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class RepositoryTest extends Tests\Cases\Unit\HistoryTestCase
{

	private const int DAY = 86_400;

	/**
	 * @return array<string, array{int, int, int, Types\HistoryTier}> start and end as seconds before now, points
	 */
	public static function periods(): array
	{
		return [
			'an hour' => [3_600, 0, 500, Types\HistoryTier::SAMPLES],
			'a day' => [self::DAY, 0, 500, Types\HistoryTier::SAMPLES],
			// 519 seconds a point: the 5-minute rollups are not wider than that, the hours are
			'three days' => [3 * self::DAY, 0, 500, Types\HistoryTier::FIVE_MINUTES],
			'a week' => [7 * self::DAY, 0, 500, Types\HistoryTier::FIVE_MINUTES],
			// 5,184 seconds a point
			'thirty days' => [30 * self::DAY, 0, 500, Types\HistoryTier::HOURS],
			'a year' => [365 * self::DAY, 0, 500, Types\HistoryTier::HOURS],
			// The samples are the finest tier but do not reach back 10 days, and the wider ones are wider than
			// the 173 seconds a point: the narrowest that reaches back
			'a day ten days ago' => [10 * self::DAY, 9 * self::DAY, 500, Types\HistoryTier::FIVE_MINUTES],
			'a day a year ago' => [366 * self::DAY, 365 * self::DAY, 500, Types\HistoryTier::HOURS],
			'a period no tier holds' => [500 * self::DAY, 499 * self::DAY, 500, Types\HistoryTier::HOURS],
			// A week in 2,016 points is the 5-minute buckets exactly
			'a week in 5-minute points' => [7 * self::DAY, 0, 2_016, Types\HistoryTier::FIVE_MINUTES],
			'a week in a few points' => [7 * self::DAY, 0, 100, Types\HistoryTier::HOURS],
			'one point' => [3_600, 0, 1, Types\HistoryTier::HOURS],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('periods')]
	public function testTierFollowsThePeriodAndThePoints(
		int $before,
		int $until,
		int $points,
		Types\HistoryTier $expected,
	): void
	{
		self::assertSame(
			$expected,
			$this->repository()->tierFor($this->at(self::NOW - $before), $this->at(self::NOW - $until), $points),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testConfiguredRetentionDecidesWhichTierReachesBack(): void
	{
		// a 5,000-point period of a day is 18 seconds a point, which the samples answer when they reach back 3 days
		self::assertSame(
			Types\HistoryTier::FIVE_MINUTES,
			$this->repository(samplesDays: 2)->tierFor(
				$this->at(self::NOW - 3 * self::DAY),
				$this->at(self::NOW - 2 * self::DAY),
				5_000,
			),
		);
		self::assertSame(
			Types\HistoryTier::SAMPLES,
			$this->repository()->tierFor(
				$this->at(self::NOW - 3 * self::DAY),
				$this->at(self::NOW - 2 * self::DAY),
				5_000,
			),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testPeriodHasToBeForward(): void
	{
		$this->expectException(DevicesExceptions\InvalidArgument::class);

		$this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at(self::NOW),
			$this->at(self::NOW),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesHasAtLeastOnePoint(): void
	{
		$this->expectException(DevicesExceptions\InvalidArgument::class);

		$this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at(self::NOW - 100),
			$this->at(self::NOW),
			0,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesFromSamples(): void
	{
		// 100 seconds a point, ten points
		$this->insertSample(self::NOW - 1_500, 10.0);
		$this->insertSample(self::NOW - 750, 20.0);
		$this->insertSample(self::NOW - 350, null);
		// Another property's
		$this->insertSample(self::NOW - 900, 99.0, self::OTHER_PROPERTY);

		$series = $this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at(self::NOW - 1_000),
			$this->at(self::NOW),
			10,
		);

		$start = self::NOW - 1_000;

		self::assertSame(Types\HistoryTier::SAMPLES, $series->tier);
		self::assertSame(100, $series->resolution);
		self::assertSame(self::NOW - 1_000, $series->from->getTimestamp());
		self::assertSame(self::NOW, $series->to->getTimestamp());
		self::assertSame(
			[
				$start,
				$start + 100,
				$start + 200,
				$start + 300,
				$start + 400,
				$start + 500,
				$start + 600,
				$start + 700,
				$start + 800,
				$start + 900,
			],
			$series->time,
		);
		// The first two points hold the 10 that came before the period. The third holds it for 50 seconds and then
		// the 20. The seventh holds the 20 for 50 seconds and then the gap, which the last three hold whole
		self::assertSame([10.0, 10.0, 10.0, 20.0, 20.0, 20.0, 20.0, null, null, null], $series->min);
		self::assertSame([10.0, 10.0, 20.0, 20.0, 20.0, 20.0, 20.0, null, null, null], $series->max);
		self::assertSame([10.0, 10.0, 15.0, 20.0, 20.0, 20.0, 20.0, null, null, null], $series->avg);
		self::assertSame([10.0, 10.0, 20.0, 20.0, 20.0, 20.0, null, null, null, null], $series->last);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesOfAPropertyWithNoHistoryIsEmpty(): void
	{
		$series = $this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at(self::NOW - 1_000),
			$this->at(self::NOW),
		);

		self::assertSame([], $series->time);
		self::assertSame([], $series->min);
		self::assertSame([], $series->max);
		self::assertSame([], $series->avg);
		self::assertSame([], $series->last);
		self::assertSame(2, $series->resolution);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesFromFiveMinuteRollups(): void
	{
		$five = Types\HistoryResolution::FIVE_MINUTES;
		$start = self::NOW - 3 * self::DAY;

		// 519 seconds a point: two rollups, 300 seconds apart, fall in a point now and then
		$this->insertRollup($five, $start, 1.0, 5.0, 3.0, 4.0);
		$this->insertRollup($five, $start + 300, 0.5, 9.0, 5.0, 8.0);
		$this->insertRollup($five, $start + 600, 2.0, 2.0, 2.0, 2.0);
		$this->insertRollup($five, $start + 900, null, null, null, null);
		// Another property's, and one before the period
		$this->insertRollup($five, $start + 300, 100.0, 100.0, 100.0, 100.0, id: self::OTHER_PROPERTY);
		$this->insertRollup($five, $start - 3_600, 100.0, 100.0, 100.0, 100.0);

		$series = $this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at($start),
			$this->at(self::NOW),
			500,
		);

		self::assertSame(Types\HistoryTier::FIVE_MINUTES, $series->tier);
		self::assertSame(519, $series->resolution);
		// Points of 519 seconds from the start: [0, 519) holds the rollups at 0 and 300, [519, 1038) the ones at
		// 600 and 900
		self::assertSame([$start, $start + 519], $series->time);
		self::assertSame([0.5, 2.0], $series->min);
		self::assertSame([9.0, 2.0], $series->max);
		self::assertSame([4.0, 2.0], $series->avg);
		self::assertSame([8.0, null], $series->last);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesFromHourRollups(): void
	{
		$hour = Types\HistoryResolution::HOUR;
		$start = self::NOW - 30 * self::DAY;

		$this->insertRollup($hour, $start, 1.0, 3.0, 2.0, 3.0);
		$this->insertRollup($hour, $start + 3_600, 5.0, 9.0, 7.0, 9.0);

		$series = $this->repository()->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at($start),
			$this->at(self::NOW),
			500,
		);

		self::assertSame(Types\HistoryTier::HOURS, $series->tier);
		self::assertSame(5_184, $series->resolution);
		// Both hours are in the first point of 5,184 seconds
		self::assertSame([$start], $series->time);
		self::assertSame([1.0], $series->min);
		self::assertSame([9.0], $series->max);
		self::assertSame([4.5], $series->avg);
		self::assertSame([9.0], $series->last);
	}

	/**
	 * The 5-minute rollups the compactor built and the series a repository reads from the samples for the same
	 * period, in points of 5 minutes, are the same numbers
	 *
	 * @throws Throwable
	 */
	public function testFiveMinuteRollupsAndSeriesFromSamplesAgree(): void
	{
		$start = self::NOW - 3 * 3_600;

		$this->insertSample($start, 10.0);
		$this->insertSample($start + 100, null);
		$this->insertSample($start + 1_800, 20.0);

		$this->compactor()->compact();

		// With the 5-minute rollups not reaching back, the samples answer a point of 300 seconds
		$series = $this->repository(fiveMinutesDays: 0)->find(
			Uuid\Uuid::fromString(self::PROPERTY),
			$this->at($start),
			$this->at($start + 3_600),
			12,
		);

		self::assertSame(Types\HistoryTier::SAMPLES, $series->tier);
		self::assertSame(300, $series->resolution);

		$rollups = array_slice($this->rollups(Types\HistoryResolution::FIVE_MINUTES), 0, 12);

		self::assertCount(12, $rollups);
		self::assertSame(array_column($rollups, 0), $series->time);
		self::assertSame(array_column($rollups, 1), $series->min);
		self::assertSame(array_column($rollups, 2), $series->max);
		self::assertSame(array_column($rollups, 3), $series->avg);
		self::assertSame(array_column($rollups, 4), $series->last);
	}

	private function at(int $time): DateTimeImmutable
	{
		return (new DateTimeImmutable())->setTimestamp($time);
	}

}
