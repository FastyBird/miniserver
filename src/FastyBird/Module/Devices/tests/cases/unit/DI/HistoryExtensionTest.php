<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DI;

use DateTimeImmutable;
use Doctrine\DBAL;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Commands;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_numeric;
use function sprintf;

/**
 * What `fbDevicesModule.history` registers (#624)
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class HistoryExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	// The container's frozen clock, 2020-04-01T12:00:00+00:00
	private const int NOW = 1_585_742_400;

	private const int T0 = self::NOW - 3_600;

	private const int DAY = 86_400;

	private const string CHANNEL = '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a';

	private const string PROPERTY = 'a3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

	/**
	 * The history is on by default, and registers what records it, what flushes it and what compacts it
	 *
	 * @throws Throwable
	 */
	public function testEnabledByDefault(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Models\History\Recorder::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\StateHistory::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\HistoryCompaction::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\History\Compactor::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\History\Repository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\CompactHistory::class, false));
	}

	/**
	 * Switched off, nothing that listens, buffers or runs a timer is registered. What reads and expires the
	 * history stays, because the data outlives the switch
	 *
	 * @throws Throwable
	 */
	public function testDisabledRegistersNothingThatListensOrRunsATimer(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.disabled.neon');

		self::assertSame([], $this->getContainer()->findByType(Models\History\Recorder::class));
		self::assertSame([], $this->getContainer()->findByType(Subscribers\StateHistory::class));
		self::assertSame([], $this->getContainer()->findByType(Subscribers\HistoryCompaction::class));

		self::assertNotNull($this->getContainer()->getByType(Models\History\Compactor::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\History\Repository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\CompactHistory::class, false));
	}

	/**
	 * `history.minInterval` reaches the recorder: with 7 seconds, a change 8 seconds after the last stored one is
	 * stored at once, where the default of 10 would have it wait
	 *
	 * @throws Throwable
	 */
	public function testConfiguredMinIntervalReachesTheRecorder(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->getContainer()->getByType(EventLoop\Status::class)->running = true;

		$recorder = $this->getContainer()->getByType(Models\History\Recorder::class);

		$property = new DevicesDocuments\Channels\Properties\Dynamic(
			Uuid\Uuid::fromString(self::PROPERTY),
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			'property',
			null,
			ValuesTypes\DataType::FLOAT,
		);

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 8));

		self::assertSame(2, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());

		$recorder->record($property, $this->state(3.0, self::T0 + 10));

		self::assertSame(1, $recorder->getWaitingCount());
	}

	/**
	 * `history.retention` reaches the compactor: with samples kept for 2 days, a sample 3 days old is deleted, where
	 * the default of 7 would keep it
	 *
	 * @throws Throwable
	 */
	public function testConfiguredRetentionReachesTheCompactor(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->insertSample(self::NOW - 3 * self::DAY, 1.0);
		$this->insertSample(self::NOW - 1_000, 2.0);

		$compaction = $this->getContainer()->getByType(Models\History\Compactor::class)->compact();

		self::assertSame(1, $compaction->expiredSamples);
		self::assertSame(1, $this->scalar(sprintf('SELECT COUNT(*) FROM %s', Entities\History\Sample::TABLE)));
	}

	/**
	 * `history.flushInterval` plus a minute is how long the compactor waits for a bucket: with 250 seconds, a
	 * bucket that ended 310 seconds ago is not complete yet, where the default of 10 seconds would have it complete
	 *
	 * @throws Throwable
	 */
	public function testFlushIntervalReachesTheCompactorAsTheSettleTime(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->insertSample(self::NOW - 1_800, 1.0);

		$this->getContainer()->getByType(Models\History\Compactor::class)->compact();

		// The settle time is 310 seconds: the newest complete bucket starts at NOW - 900, where 70 would make it NOW - 600
		self::assertSame(
			self::NOW - 900,
			$this->scalar(sprintf('SELECT MAX(bucket_start) FROM %s', Entities\History\Rollup::TABLE)),
		);
	}

	/**
	 * `history.retention` reaches the repository: the samples reach back 2 days, so 3 days back is answered by
	 * the 5-minute rollups where the default of 7 days would have the samples answer it
	 *
	 * @throws Throwable
	 */
	public function testConfiguredRetentionReachesTheRepository(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$from = (new DateTimeImmutable())->setTimestamp(self::NOW - 3 * self::DAY);
		$to = (new DateTimeImmutable())->setTimestamp(self::NOW - 3 * self::DAY + 8_640);

		self::assertSame(
			DevicesTypes\HistoryTier::FIVE_MINUTES,
			$this->getContainer()->getByType(Models\History\Repository::class)->tierFor($from, $to, 500),
		);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function insertSample(int $time, float $value): void
	{
		$this->getDb()->executeStatement(
			sprintf('INSERT INTO %s (property_id, sampled_at, value) VALUES (?, ?, ?)', Entities\History\Sample::TABLE),
			[Uuid\Uuid::fromString(self::PROPERTY)->getBytes(), $time, $value],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER, DBAL\ParameterType::STRING],
		);
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function state(float $value, int $time): Tests\Fixtures\Dummy\ChannelPropertyState
	{
		return new Tests\Fixtures\Dummy\ChannelPropertyState(
			Uuid\Uuid::fromString(self::PROPERTY),
			actualValue: $value,
			valid: true,
			updatedAt: (new DateTimeImmutable())->setTimestamp($time),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function scalar(string $sql): int
	{
		$value = $this->getDb()->fetchOne($sql);

		assert(is_numeric($value));

		return (int) $value;
	}

}
