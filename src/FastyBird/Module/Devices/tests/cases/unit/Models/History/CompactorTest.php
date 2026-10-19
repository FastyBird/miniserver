<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\History;

use Doctrine\DBAL;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
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

	/**
	 * What the history of a property that was deleted does once the property is gone: its newest sample, which is
	 * never expired, would otherwise keep it alive and keep producing a rollup for every new bucket
	 *
	 * @throws Throwable
	 */
	public function testHistoryOfADeletedPropertyIsPurgedAndDoesNotGrowBack(): void
	{
		$five = Types\HistoryResolution::FIVE_MINUTES;
		$hour = Types\HistoryResolution::HOUR;

		// A property that last changed 100 days ago, rolled up while it exists
		$this->insertSample(self::NOW - 100 * self::DAY, 6.0);
		$this->insertSample(self::NOW - 100 * self::DAY, 7.0, self::OTHER_PROPERTY);

		$this->compactor()->compact();

		self::assertNotSame(0, $this->countRollups());

		// The first one is deleted. The history of the second goes on
		$this->getDb()->executeStatement(
			'DELETE FROM fb_devices_module_channels_properties WHERE property_id = ?',
			[Uuid\Uuid::fromString(self::PROPERTY)->getBytes()],
			[DBAL\ParameterType::BINARY],
		);

		foreach ([0, 30, 60, 400] as $days) {
			$compaction = $this->compactor(now: self::NOW + $days * self::DAY)->compact();

			self::assertSame([], $this->rows(), sprintf('day +%d', $days));
			self::assertSame([], $this->rollups($five), sprintf('day +%d', $days));
			self::assertSame([], $this->rollups($hour), sprintf('day +%d', $days));
			self::assertSame(0, $compaction->failed);
			// The first run purges what the dead one had, and the later ones find nothing of it: the one property
			// they list is the living one, whose history goes on
			self::assertSame($days === 0, $compaction->purged > 0, sprintf('day +%d', $days));
			self::assertSame($days === 0 ? 2 : 1, $compaction->properties, sprintf('day +%d', $days));
			self::assertNotSame([], $this->rows(self::OTHER_PROPERTY), sprintf('day +%d', $days));
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testHistoryOfAPropertyThatNeverExistedIsPurged(): void
	{
		$five = Types\HistoryResolution::FIVE_MINUTES;

		$this->insertSample(self::NOW - 1_800, 1.0, self::DEAD_PROPERTY);
		$this->insertSample(self::NOW - 900, 2.0, self::DEAD_PROPERTY);
		$this->insertRollup($five, self::NOW - 1_800, 1.0, 1.0, 1.0, 1.0, id: self::DEAD_PROPERTY);
		$this->insertRollup(
			Types\HistoryResolution::HOUR,
			self::NOW - 7_200,
			1.0,
			1.0,
			1.0,
			1.0,
			id: self::DEAD_PROPERTY,
		);
		$this->insertSample(self::NOW - 1_800, 9.0);

		$compaction = $this->compactor()->compact();

		self::assertSame(4, $compaction->purged);
		self::assertSame([], $this->rows(self::DEAD_PROPERTY));
		self::assertSame([], $this->rollups($five, self::DEAD_PROPERTY));
		self::assertSame([], $this->rollups(Types\HistoryResolution::HOUR, self::DEAD_PROPERTY));
		// The living one is compacted as ever
		self::assertCount(1, $this->rows());
		self::assertNotSame([], $this->rollups($five));
	}

	/**
	 * A property of the connector and of the device tables exists as well as one of the channel table
	 *
	 * @throws Throwable
	 */
	public function testPropertiesOfTheOtherTablesAreAlive(): void
	{
		$connector = '3d2b8a1c-9e4f-4c61-b7a3-5f0e2d8c6b41';
		$device = '7e9c1f40-2a5b-4d83-9c16-8b3d4e5f6a72';

		$this->getDb()->executeStatement(
			'INSERT INTO fb_devices_module_connectors_properties (property_id, connector_id, property_type,'
			. ' property_identifier, property_name, property_settable, property_queryable, property_data_type,'
			. ' created_at, updated_at)'
			. ' SELECT ?, connector_id, \'dynamic\', \'probe\', NULL, 0, 0, \'float\', NOW(), NOW()'
			. ' FROM fb_devices_module_connectors LIMIT 1',
			[Uuid\Uuid::fromString($connector)->getBytes()],
			[DBAL\ParameterType::BINARY],
		);
		$this->getDb()->executeStatement(
			'INSERT INTO fb_devices_module_devices_properties (property_id, device_id, property_type,'
			. ' property_identifier, property_name, property_settable, property_queryable, property_data_type,'
			. ' created_at, updated_at)'
			. ' SELECT ?, device_id, \'dynamic\', \'probe\', NULL, 0, 0, \'float\', NOW(), NOW()'
			. ' FROM fb_devices_module_devices LIMIT 1',
			[Uuid\Uuid::fromString($device)->getBytes()],
			[DBAL\ParameterType::BINARY],
		);

		$this->insertSample(self::NOW - 1_800, 1.0, $connector);
		$this->insertSample(self::NOW - 1_800, 2.0, $device);

		$compaction = $this->compactor()->compact();

		self::assertSame(0, $compaction->purged);
		self::assertSame(2, $compaction->properties);
		self::assertCount(1, $this->rows($connector));
		self::assertCount(1, $this->rows($device));
		self::assertNotSame([], $this->rollups(Types\HistoryResolution::FIVE_MINUTES, $connector));
		self::assertNotSame([], $this->rollups(Types\HistoryResolution::FIVE_MINUTES, $device));
	}

	/**
	 * The history of a dead property is deleted in batches, until none is left
	 *
	 * @throws Throwable
	 */
	public function testPurgeRunsUntilTheHistoryIsGone(): void
	{
		$this->getDb()->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, sampled_at, value) SELECT ?, seq, 1 FROM seq_1_to_10001',
				Entities\History\Sample::TABLE,
			),
			[Uuid\Uuid::fromString(self::DEAD_PROPERTY)->getBytes()],
			[DBAL\ParameterType::BINARY],
		);

		$compaction = $this->compactor()->compact();

		self::assertSame(10_001, $compaction->purged);
		self::assertSame([], $this->rows(self::DEAD_PROPERTY));
	}

	/**
	 * A property that exists but has been silent for longer than the sample retention is still rolled up: its
	 * value is its value, and a chart over a month shows it. Its rows are bounded, as every property's are, by the
	 * retention of the rollups
	 *
	 * @throws Throwable
	 */
	public function testSilentPropertyIsStillRolledUp(): void
	{
		$this->insertSample(self::NOW - 10 * self::DAY, 6.0);

		$this->compactor()->compact();

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame(self::NOW - 600, $rollups[count($rollups) - 1][0]);
		self::assertSame([[self::NOW - 10 * self::DAY, 6.0]], $this->rows());
	}

	/**
	 * A bucket may still be waited for by a process that holds a change for minInterval, flushes it on its timer
	 * and commits it, so the settle time is what the caller wires; the compactor honours it to the second
	 *
	 * @throws Throwable
	 */
	public function testSettleTimeLongerThanAMinuteIsHonoured(): void
	{
		$this->insertSample(self::NOW - 1_800, 1.0);

		// 700 seconds: the newest complete bucket starts at NOW - 1,200
		$this->compactor(settle: 700)->compact();

		$times = array_column($this->rollups(Types\HistoryResolution::FIVE_MINUTES), 0);

		self::assertSame(self::NOW - 1_200, $times[count($times) - 1]);
	}

	/**
	 * @throws Throwable
	 */
	public function testAverageOfValuesNearTheLargestDoubleIsFinite(): void
	{
		$start = self::NOW - 1_800;

		$this->insertSample($start, 1.7e308);
		$this->insertSample($start + 100, 1.7e308);
		$this->insertSample($start + 200, -1.7e308);

		$compaction = $this->compactor()->compact();

		self::assertSame(0, $compaction->failed);
		self::assertSame([], $this->logger->at('error'));

		$rollups = $this->rollups(Types\HistoryResolution::FIVE_MINUTES);

		self::assertSame(-1.7e308, $rollups[0][1]);
		self::assertSame(1.7e308, $rollups[0][2]);
		// 1.7e308 for 200 seconds, then -1.7e308 for 100: a third of it
		self::assertEqualsWithDelta(1.7e308 / 3, $rollups[0][3] ?? 0.0, 1.7e308 * 1e-12);
		// The same bucket is carried on as -1.7e308
		self::assertSame(-1.7e308, $rollups[1][3]);
	}

	/**
	 * A first bucket whose start is before the cutoff of the retention would be written and expired in one run, so
	 * with a time that is not aligned to a bucket the first one is the first at or after the cutoff
	 *
	 * @throws Throwable
	 */
	public function testNoBucketIsWrittenOnlyToBeExpiredWhenTheTimeIsNotAligned(): void
	{
		$this->insertSample(self::NOW - 100 * self::DAY, 6.0);

		$compaction = $this->compactor(hoursDays: 2, now: self::NOW + 137)->compact();

		self::assertSame(0, $compaction->expiredFiveMinutes);
		self::assertSame(0, $compaction->expiredHours);
		self::assertCount($compaction->fiveMinutes, $this->rollups(Types\HistoryResolution::FIVE_MINUTES));

		$first = $this->rollups(Types\HistoryResolution::FIVE_MINUTES)[0][0];

		self::assertGreaterThanOrEqual(self::NOW + 137 - 30 * self::DAY, $first);
		self::assertSame(0, $first % 300);
		self::assertLessThan(300, $first - (self::NOW + 137 - 30 * self::DAY));

		$hours = $this->rollups(Types\HistoryResolution::HOUR);

		self::assertGreaterThanOrEqual(self::NOW + 137 - 2 * self::DAY, $hours[0][0]);
		self::assertSame(0, $hours[0][0] % 3_600);
		self::assertLessThan(3_600, $hours[0][0] - (self::NOW + 137 - 2 * self::DAY));
	}

	/**
	 * A sample exactly as old as the retention is kept, and one a second older goes
	 *
	 * @throws Throwable
	 */
	public function testSampleAtExactlyTheRetentionIsKept(): void
	{
		$this->insertSample(self::NOW - 7 * self::DAY - 1, 1.0);
		$this->insertSample(self::NOW - 7 * self::DAY, 2.0);
		$this->insertSample(self::NOW - 1_000, 3.0);

		$this->compactor()->compact();

		self::assertSame([[self::NOW - 7 * self::DAY, 2.0], [self::NOW - 1_000, 3.0]], $this->rows());
	}

	/**
	 * Likewise a rollup exactly as old as its retention
	 *
	 * @throws Throwable
	 */
	public function testRollupAtExactlyTheRetentionIsKept(): void
	{
		$five = Types\HistoryResolution::FIVE_MINUTES;
		$hour = Types\HistoryResolution::HOUR;

		$this->insertRollup($five, self::NOW - 30 * self::DAY - 300, 1.0, 1.0, 1.0, 1.0);
		$this->insertRollup($five, self::NOW - 30 * self::DAY, 2.0, 2.0, 2.0, 2.0);
		$this->insertRollup($hour, self::NOW - 365 * self::DAY - 3_600, 3.0, 3.0, 3.0, 3.0);
		$this->insertRollup($hour, self::NOW - 365 * self::DAY, 4.0, 4.0, 4.0, 4.0);

		$this->compactor()->compact();

		self::assertSame([self::NOW - 30 * self::DAY], array_column($this->rollups($five), 0));
		self::assertSame(self::NOW - 365 * self::DAY, $this->rollups($hour)[0][0]);
		self::assertSame(4.0, $this->rollups($hour)[0][1]);
	}

	/**
	 * The first hour starts on the hour, whatever the first 5-minute bucket is
	 *
	 * @throws Throwable
	 */
	public function testFirstHourStartsOnTheHour(): void
	{
		// The first bucket is at 10:35, NOW being 12:00
		$this->insertSample(self::NOW - 5_100, 8.0);

		$this->compactor()->compact();

		$hours = $this->rollups(Types\HistoryResolution::HOUR);

		self::assertCount(1, $hours);
		self::assertSame(self::NOW - 7_200, $hours[0][0]);
		self::assertSame(8.0, $hours[0][1]);
	}

	/**
	 * Hours older than their retention are not built from 5-minute rows that are older still
	 *
	 * @throws Throwable
	 */
	public function testNoHourIsBuiltOnlyToBeExpired(): void
	{
		$this->insertRollup(Types\HistoryResolution::FIVE_MINUTES, self::NOW - 10 * self::DAY, 1.0, 1.0, 1.0, 1.0);

		$compaction = $this->compactor(hoursDays: 1)->compact();

		self::assertSame(0, $compaction->hours);
		self::assertSame([], $this->rollups(Types\HistoryResolution::HOUR));
	}

}
