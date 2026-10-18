<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\History;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Throwable;
use function array_column;
use function count;
use function sprintf;

/**
 * What the compactor rolls up and expires (#624)
 *
 * The container's clock is frozen at NOW, 12:00:00 UTC, which is aligned to 5 minutes and to an hour. With a
 * settle of 90 seconds the newest complete 5-minute bucket is the one that starts at NOW - 600, and the newest
 * complete hour is the one that ends at 11:00.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class CompactorTest extends Tests\Cases\Unit\HistoryTestCase
{

	private const int DAY = 86_400;

	/**
	 * @throws Throwable
	 */
	public function testFiveMinuteRollupsAreTimeWeighted(): void
	{
		$start = self::NOW - 1_800;

		// 10 for 100 seconds, 20 for 100, 30 for 100, then 30 on
		$this->insertSample($start, 10.0);
		$this->insertSample($start + 100, 20.0);
		$this->insertSample($start + 200, 30.0);

		$compaction = $this->compactor()->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame(1, $compaction->properties);
		self::assertSame(0, $compaction->failed);

		// From the first bucket to the newest complete one, NOW - 600, and the one that follows is not there
		self::assertSame(
			[$start, $start + 300, $start + 600, $start + 900, $start + 1_200],
			array_column($rollups, 0),
		);
		self::assertCount($compaction->fiveMinutes, $rollups);
		self::assertSame([$start, 10.0, 30.0, 20.0, 30.0, 3], $rollups[0]);
		// No sample in it: the value that held carries on
		self::assertSame([$start + 300, 30.0, 30.0, 30.0, 30.0, 0], $rollups[1]);
		self::assertSame([$start + 1_200, 30.0, 30.0, 30.0, 30.0, 0], $rollups[4]);
	}

	/**
	 * @throws Throwable
	 */
	public function testGapsAreCarriedAsNullsAndWeighNothing(): void
	{
		$start = self::NOW - 1_800;

		$this->insertSample($start, 30.0);
		$this->insertSample($start + 100, null);
		$this->insertSample($start + 700, 40.0);

		$this->compactor()->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		// 30 for 100 seconds, then a gap
		self::assertSame([$start, 30.0, 30.0, 30.0, null, 2], $rollups[0]);
		// A bucket of nothing but the gap
		self::assertSame([$start + 300, null, null, null, null, 0], $rollups[1]);
		// The gap for 100 seconds, then 40 for 200
		self::assertSame([$start + 600, 40.0, 40.0, 40.0, 40.0, 1], $rollups[2]);
	}

	/**
	 * @throws Throwable
	 */
	public function testNothingIsKnownBeforeTheFirstSample(): void
	{
		$start = self::NOW - 1_800;

		$this->insertSample($start + 150, 5.0);

		$this->compactor()->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame($start, $rollups[0][0]);
		self::assertSame([$start, 5.0, 5.0, 5.0, 5.0, 1], $rollups[0]);
	}

	/**
	 * A bucket is complete once its end and the settle time have passed
	 *
	 * @throws Throwable
	 */
	public function testOnlyCompleteBucketsAreBuilt(): void
	{
		$this->insertSample(self::NOW - 1_800, 1.0);
		$this->insertSample(self::NOW - 100, 2.0);

		$this->compactor(settle: 90)->compact();

		$times = array_column($this->rollups(Types\HistoryResolution::FIVE_MINUTES), 0);

		// [NOW - 300, NOW) ends at NOW, which is not 90 seconds ago yet
		self::assertSame(self::NOW - 600, $times[count($times) - 1]);

		// A settle that is longer holds the newest bucket back
		$this->getDb()->executeStatement(sprintf('DELETE FROM %s', Entities\History\Rollup::TABLE));

		$this->compactor(settle: 400)->compact();

		$times = array_column($this->rollups(Types\HistoryResolution::FIVE_MINUTES), 0);

		self::assertSame(self::NOW - 900, $times[count($times) - 1]);
	}

	/**
	 * @throws Throwable
	 */
	public function testHourRollupsAreBuiltFromTheFiveMinuteOnes(): void
	{
		$start = self::NOW - 3 * 3_600;

		// 10 for half an hour, then 20
		$this->insertSample($start, 10.0);
		$this->insertSample($start + 1_800, 20.0);

		$compaction = $this->compactor()->compact();

		$hours = $this->rollups(Types\HistoryResolution::HOUR);

		self::assertSame(2, $compaction->hours);
		// The hours that end by 11:00: 09:00 and 10:00
		self::assertSame([$start, 10.0, 20.0, 15.0, 20.0, 2], $hours[0]);
		self::assertSame([$start + 3_600, 20.0, 20.0, 20.0, 20.0, 0], $hours[1]);
		self::assertCount(2, $hours);
	}

	/**
	 * @throws Throwable
	 */
	public function testRepeatedRunsLeaveTheSameRollups(): void
	{
		$start = self::NOW - 3 * 3_600;

		$this->insertSample($start, 10.0);
		$this->insertSample($start + 1_800, 20.0);

		$compactor = $this->compactor();

		$compactor->compact();

		$five = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);
		$hours = $this->rollups(Types\HistoryResolution::HOUR);

		$compactor->compact();
		$compactor->compact();

		self::assertSame($five, $this->rollups(Types\HistoryResolution::FIVE_MINUTES));
		self::assertSame($hours, $this->rollups(Types\HistoryResolution::HOUR));
	}

	/**
	 * The last bucket stored is built again, so a sample that came late for it is picked up
	 *
	 * @throws Throwable
	 */
	public function testLastBucketIsBuiltAgain(): void
	{
		$this->insertSample(self::NOW - 1_800, 10.0);

		$compactor = $this->compactor();

		$compactor->compact();

		$last = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);
		$last = $last[count($last) - 1];

		self::assertSame([self::NOW - 600, 10.0, 10.0, 10.0, 10.0, 0], $last);

		$this->insertSample(self::NOW - 450, 99.0);

		$compactor->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame(
			[self::NOW - 600, 10.0, 99.0, 10.0 * 150 / 300 + 99.0 * 150 / 300, 99.0, 1],
			$rollups[count($rollups) - 1],
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testSamplesPastTheirRetentionAreDeletedAndTheNewestOneStays(): void
	{
		$this->insertSample(self::NOW - 10 * self::DAY, 1.0);
		$this->insertSample(self::NOW - 9 * self::DAY, 2.0);
		$this->insertSample(self::NOW - self::DAY, 3.0);

		// A property whose newest sample is older than the retention: its value is still its value
		$this->insertSample(self::NOW - 20 * self::DAY, 4.0, self::OTHER_PROPERTY);
		$this->insertSample(self::NOW - 15 * self::DAY, 5.0, self::OTHER_PROPERTY);

		$compaction = $this->compactor()->compact();

		self::assertSame([[self::NOW - self::DAY, 3.0]], $this->rows());
		self::assertSame([[self::NOW - 15 * self::DAY, 5.0]], $this->rows(self::OTHER_PROPERTY));
		self::assertSame(3, $compaction->expiredSamples);
	}

	/**
	 * @throws Throwable
	 */
	public function testRollupsPastTheirRetentionAreDeleted(): void
	{
		$five = Types\HistoryResolution::FIVE_MINUTES;
		$hour = Types\HistoryResolution::HOUR;

		// A property with rollups and no sample at all
		$this->insertRollup($five, self::NOW - 31 * self::DAY, 1.0, 1.0, 1.0, 1.0);
		$this->insertRollup($five, self::NOW - 29 * self::DAY, 2.0, 2.0, 2.0, 2.0);
		$this->insertRollup($hour, self::NOW - 400 * self::DAY, 3.0, 3.0, 3.0, 3.0);
		$this->insertRollup($hour, self::NOW - 360 * self::DAY, 4.0, 4.0, 4.0, 4.0);

		$compaction = $this->compactor()->compact();

		self::assertSame([[self::NOW - 29 * self::DAY, 2.0, 2.0, 2.0, 2.0, 1]], $this->rollups($five));
		// The hours are built from the 5-minute rollups before those expire, so the 5-minute rollup that went
		// has its hour, and the one that is left has its own
		self::assertSame(
			[
				[self::NOW - 360 * self::DAY, 4.0, 4.0, 4.0, 4.0, 1],
				[self::NOW - 31 * self::DAY, 1.0, 1.0, 1.0, 1.0, 1],
				[self::NOW - 29 * self::DAY, 2.0, 2.0, 2.0, 2.0, 1],
			],
			$this->rollups($hour),
		);
		self::assertSame(1, $compaction->expiredFiveMinutes);
		self::assertSame(1, $compaction->expiredHours);
	}

	/**
	 * @throws Throwable
	 */
	public function testConfiguredRetentionDecidesWhatExpires(): void
	{
		$this->insertSample(self::NOW - 3 * self::DAY, 1.0);
		$this->insertSample(self::NOW - 2 * self::DAY, 2.0);
		$this->insertSample(self::NOW - 1_000, 3.0);

		$this->compactor(samplesDays: 1)->compact();

		self::assertSame([[self::NOW - 1_000, 3.0]], $this->rows());
	}

	/**
	 * A property that has not changed for longer than the retention still gets its rollups, from the retention
	 * on and not from its first sample
	 *
	 * @throws Throwable
	 */
	public function testQuietPropertyIsRolledUpWithinTheRetentionOfTheRollups(): void
	{
		$this->insertSample(self::NOW - 100 * self::DAY, 6.0);

		$compaction = $this->compactor()->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame(self::NOW - 30 * self::DAY, $rollups[0][0]);
		self::assertSame([self::NOW - 30 * self::DAY, 6.0, 6.0, 6.0, 6.0, 0], $rollups[0]);
		self::assertSame(self::NOW - 600, $rollups[count($rollups) - 1][0]);
		// 30 days of 5-minute buckets, less the newest, which is not complete
		self::assertCount(30 * 288 - 1, $rollups);
		// And no bucket was written only to be expired in the same run
		self::assertSame(30 * 288 - 1, $compaction->fiveMinutes);
	}

	/**
	 * @throws Throwable
	 */
	public function testPropertiesAreCompactedApartFromEachOther(): void
	{
		$this->insertSample(self::NOW - 1_800, 1.0);
		$this->insertSample(self::NOW - 1_800, 9.0, self::OTHER_PROPERTY);

		$compaction = $this->compactor()->compact();

		self::assertSame(2, $compaction->properties);
		self::assertSame(1.0, $this->rollups(Types\HistoryResolution::FIVE_MINUTES)[0][4]);
		self::assertSame(9.0, $this->rollups(Types\HistoryResolution::FIVE_MINUTES, self::OTHER_PROPERTY)[0][4]);
	}

	/**
	 * A failure is logged and counted, and the history is left as it was
	 *
	 * @throws Throwable
	 */
	public function testFailureIsLoggedAndCounted(): void
	{
		$this->insertSample(self::NOW - 1_800, 1.0);
		$this->insertSample(self::NOW - 1_800, 9.0, self::OTHER_PROPERTY);

		$this->getDb()->executeStatement(
			sprintf(
				'RENAME TABLE %s TO %s_away',
				Entities\History\Rollup::TABLE,
				Entities\History\Rollup::TABLE,
			),
		);

		$compactor = $this->compactor();

		$compaction = $compactor->compact();

		self::assertSame(0, $compaction->properties);
		self::assertSame(1, $compaction->failed);
		self::assertCount(1, $this->logger->at('error'));
		self::assertSame('History properties could not be listed', $this->logger->at('error')[0]['message']);
		self::assertCount(1, $this->rows());
		self::assertCount(1, $this->rows(self::OTHER_PROPERTY));
	}

	/**
	 * One property that cannot be compacted does not stop the others
	 *
	 * @throws Throwable
	 */
	public function testOneFailingPropertyDoesNotStopTheOthers(): void
	{
		$this->insertSample(self::NOW - 1_800, 1.0);
		$this->insertSample(self::NOW - 1_800, 9.0, self::OTHER_PROPERTY);

		// A value the rollup columns reject for one property only: the samples table takes it, the rollups do not
		$this->getDb()->executeStatement(
			sprintf('ALTER TABLE %s ADD CONSTRAINT never CHECK (value_avg <> 1)', Entities\History\Rollup::TABLE),
		);

		$compaction = $this->compactor()->compact();

		self::assertSame(1, $compaction->failed);
		self::assertSame(1, $compaction->properties);
		self::assertCount(1, $this->logger->at('error'));
		self::assertSame('History of a property could not be compacted', $this->logger->at('error')[0]['message']);
		// The failed property's transaction was rolled back
		self::assertSame([], $this->rollups(Types\HistoryResolution::FIVE_MINUTES));
		self::assertNotSame([], $this->rollups(Types\HistoryResolution::FIVE_MINUTES, self::OTHER_PROPERTY));
	}

}
