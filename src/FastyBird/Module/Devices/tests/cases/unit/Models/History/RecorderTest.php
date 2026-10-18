<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\History;

use DateTimeImmutable;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_map;
use function sprintf;
use function str_starts_with;
use function substr_count;
use const INF;
use const NAN;

/**
 * What the recorder stores of a property's state changes (#624)
 *
 * The recorder writes to the test database, and the tests read the table back. A recorder whose event
 * loop does not run writes each change at once, so the rules about what is stored are tested through it.
 * One whose loop runs buffers, and the tests flush it by hand.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class RecorderTest extends Tests\Cases\Unit\HistoryTestCase
{

	/**
	 * @return array<string, array{ValuesTypes\DataType, bool|float|int|Payloads\Payload, float}>
	 */
	public static function recordedTypes(): array
	{
		return [
			'char' => [ValuesTypes\DataType::CHAR, -5, -5.0],
			'uchar' => [ValuesTypes\DataType::UCHAR, 5, 5.0],
			'short' => [ValuesTypes\DataType::SHORT, -300, -300.0],
			'ushort' => [ValuesTypes\DataType::USHORT, 300, 300.0],
			'int' => [ValuesTypes\DataType::INT, -70_000, -70_000.0],
			'uint' => [ValuesTypes\DataType::UINT, 70_000, 70_000.0],
			'float' => [ValuesTypes\DataType::FLOAT, 21.5, 21.5],
			'bool true' => [ValuesTypes\DataType::BOOLEAN, true, 1.0],
			'bool false' => [ValuesTypes\DataType::BOOLEAN, false, 0.0],
			'switch on' => [ValuesTypes\DataType::SWITCH, Payloads\Switcher::ON, 1.0],
			'switch off' => [ValuesTypes\DataType::SWITCH, Payloads\Switcher::OFF, 0.0],
		];
	}

	/**
	 * @return array<string, array{ValuesTypes\DataType}>
	 */
	public static function skippedTypes(): array
	{
		return [
			'string' => [ValuesTypes\DataType::STRING],
			'enum' => [ValuesTypes\DataType::ENUM],
			'date' => [ValuesTypes\DataType::DATE],
			'time' => [ValuesTypes\DataType::TIME],
			'datetime' => [ValuesTypes\DataType::DATETIME],
			'color' => [ValuesTypes\DataType::COLOR],
			'button' => [ValuesTypes\DataType::BUTTON],
			'cover' => [ValuesTypes\DataType::COVER],
			'unknown' => [ValuesTypes\DataType::UNKNOWN],
		];
	}

	/**
	 * A data type added later has to be decided on: recorded or skipped, in one of the two providers
	 */
	public function testEveryDataTypeIsDecided(): void
	{
		$decided = array_map(
			static fn (array $case): string => $case[0]->value,
			[...self::recordedTypes(), ...self::skippedTypes()],
		);

		foreach (ValuesTypes\DataType::cases() as $dataType) {
			self::assertContains(
				$dataType->value,
				$decided,
				sprintf('%s is neither recorded nor skipped', $dataType->value),
			);
		}
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('recordedTypes')]
	public function testRecordedTypeIsStoredAsANumber(
		ValuesTypes\DataType $dataType,
		bool|float|int|Payloads\Payload $value,
		float $expected,
	): void
	{
		$recorder = $this->recorder();

		$recorder->record($this->property($dataType), $this->state($value, self::T0));

		self::assertSame([[self::T0, $expected]], $this->rows());
	}

	/**
	 * Skipped for its type: the valid state holds a number, so the value alone would have been recorded, and
	 * an invalid state of the same property stores no gap either
	 *
	 * @throws Throwable
	 */
	#[DataProvider('skippedTypes')]
	public function testSkippedTypeIsNotStored(ValuesTypes\DataType $dataType): void
	{
		$recorder = $this->recorder();

		$recorder->record($this->property($dataType), $this->state(1, self::T0));
		$recorder->record($this->property($dataType), $this->state(null, self::T0 + 60, valid: false));

		self::assertSame([], $this->rows());
		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());
	}

	/**
	 * @throws Throwable
	 */
	public function testSwitchToggleIsACommandAndIsNotStored(): void
	{
		$recorder = $this->recorder();

		$recorder->record(
			$this->property(ValuesTypes\DataType::SWITCH),
			$this->state(Payloads\Switcher::TOGGLE, self::T0),
		);

		self::assertSame([], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testValueTheHistoryCannotKeepIsNotStored(): void
	{
		$recorder = $this->recorder();

		// No value yet, and the two floats a double column does not hold
		$recorder->record($this->property(), $this->state(null, self::T0));
		$recorder->record($this->property(), $this->state(NAN, self::T0 + 60));
		$recorder->record($this->property(), $this->state(INF, self::T0 + 120));
		$recorder->record($this->property(ValuesTypes\DataType::BOOLEAN), $this->state(1, self::T0 + 180));
		$recorder->record($this->property(ValuesTypes\DataType::SWITCH), $this->state('switch_on', self::T0 + 240));

		self::assertSame([], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testMappedPropertyIsNotRecordedAndItsParentIs(): void
	{
		$recorder = $this->recorder(10, true);

		$recorder->record($this->mappedProperty(), $this->state(21.5, self::T0, id: self::OTHER_PROPERTY));

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());

		$recorder->record($this->property(), $this->state(21.5, self::T0));
		$recorder->flush();

		self::assertSame([], $this->rows(self::OTHER_PROPERTY));
		self::assertSame([[self::T0, 21.5]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testEqualValueIsStoredOnce(): void
	{
		$recorder = $this->recorder(0);

		$recorder->record($this->property(), $this->state(20.0, self::T0));
		$recorder->record($this->property(), $this->state(20.0, self::T0 + 60));
		$recorder->record($this->property(), $this->state(21.0, self::T0 + 120));
		$recorder->record($this->property(), $this->state(21.0, self::T0 + 180));
		$recorder->record($this->property(), $this->state(20.0, self::T0 + 240));

		self::assertSame([[self::T0, 20.0], [self::T0 + 120, 21.0], [self::T0 + 240, 20.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testPropertiesAreDeduplicatedApartFromEachOther(): void
	{
		$recorder = $this->recorder(0);

		$recorder->record($this->property(), $this->state(20.0, self::T0));
		$recorder->record(
			$this->property(id: self::OTHER_PROPERTY),
			$this->state(20.0, self::T0 + 10, id: self::OTHER_PROPERTY),
		);

		self::assertSame([[self::T0, 20.0]], $this->rows());
		self::assertSame([[self::T0 + 10, 20.0]], $this->rows(self::OTHER_PROPERTY));
	}

	/**
	 * @throws Throwable
	 */
	public function testNumericChangeSmallerThanTheStepIsNotStored(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property(step: 0.5);

		$recorder->record($property, $this->state(20.0, self::T0));
		// Drift is measured from the last stored value, not from the last change
		$recorder->record($property, $this->state(20.3, self::T0 + 60));
		$recorder->record($property, $this->state(20.4, self::T0 + 120));
		$recorder->record($property, $this->state(20.6, self::T0 + 180));
		$recorder->record($property, $this->state(21.0, self::T0 + 240));
		$recorder->record($property, $this->state(20.8, self::T0 + 300));

		// 21.0 is 0.4 from the stored 20.6 and 20.8 is 0.2 from it: neither reaches the step
		self::assertSame([[self::T0, 20.0], [self::T0 + 180, 20.6]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testChangeOfExactlyTheStepIsStored(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property(step: 1);

		$recorder->record($property, $this->state(20.0, self::T0));
		$recorder->record($property, $this->state(21.0, self::T0 + 60));

		self::assertSame([[self::T0, 20.0], [self::T0 + 60, 21.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testEveryChangeIsStoredWhenThePropertyHasNoStep(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property();

		$recorder->record($property, $this->state(20.0, self::T0));
		$recorder->record($property, $this->state(20.001, self::T0 + 60));

		self::assertSame([[self::T0, 20.0], [self::T0 + 60, 20.001]], $this->rows());
	}

	/**
	 * A step is the resolution of a number. A boolean's change is never below it, whatever the step says
	 *
	 * @throws Throwable
	 */
	public function testBooleanIgnoresTheStep(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property(ValuesTypes\DataType::BOOLEAN, step: 2);

		$recorder->record($property, $this->state(true, self::T0));
		$recorder->record($property, $this->state(false, self::T0 + 60));

		self::assertSame([[self::T0, 1.0], [self::T0 + 60, 0.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testInvalidStateStoresOneGap(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property();

		$recorder->record($property, $this->state(20.0, self::T0));
		$recorder->record($property, $this->state(null, self::T0 + 60, valid: false));
		$recorder->record($property, $this->state(null, self::T0 + 120, valid: false));
		$recorder->record($property, $this->state(20.0, self::T0 + 180));

		self::assertSame([[self::T0, 20.0], [self::T0 + 60, null], [self::T0 + 180, 20.0]], $this->rows());
	}

	/**
	 * The value an invalid state still holds is not the value of the time: the gap wins
	 *
	 * @throws Throwable
	 */
	public function testGapIgnoresTheValueTheInvalidStateHolds(): void
	{
		$recorder = $this->recorder(0);

		$recorder->record($this->property(), $this->state(20.0, self::T0, valid: false));

		self::assertSame([[self::T0, null]], $this->rows());
	}

	/**
	 * A change across a gap is not a deadband change: the line starts again wherever it is
	 *
	 * @throws Throwable
	 */
	public function testDeadbandDoesNotApplyAcrossAGap(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property(step: 5);

		$recorder->record($property, $this->state(20.0, self::T0));
		$recorder->record($property, $this->state(null, self::T0 + 60, valid: false));
		$recorder->record($property, $this->state(20.1, self::T0 + 120));

		self::assertSame([[self::T0, 20.0], [self::T0 + 60, null], [self::T0 + 120, 20.1]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testLastChangeWithinMinIntervalWins(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 3));
		$recorder->record($property, $this->state(3.0, self::T0 + 6));

		self::assertSame(1, $recorder->getBufferedCount());
		self::assertSame(1, $recorder->getWaitingCount());

		$recorder->drain();

		self::assertSame([[self::T0, 1.0], [self::T0 + 6, 3.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testChangeAtMinIntervalIsStoredAtOnce(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 10));

		self::assertSame(2, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());
	}

	/**
	 * @throws Throwable
	 */
	public function testChangeThatComesBackDropsTheWaitingOne(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(5.0, self::T0 + 3));

		self::assertSame(1, $recorder->getWaitingCount());

		$recorder->record($property, $this->state(1.0, self::T0 + 5));

		self::assertSame(0, $recorder->getWaitingCount());

		$recorder->drain();

		self::assertSame([[self::T0, 1.0]], $this->rows());
	}

	/**
	 * The window of a waiting change opens at the last stored sample and does not slide with each change, so a
	 * value that changes every few seconds is written once a window. That bounds the writes, not the spacing
	 * of the samples: the waiting change keeps its own time, and the samples here are 8 and 4 seconds apart
	 *
	 * @throws Throwable
	 */
	public function testWindowOfAWaitingChangeDoesNotSlide(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 4));
		$recorder->record($property, $this->state(3.0, self::T0 + 8));
		$recorder->record($property, $this->state(4.0, self::T0 + 12));

		self::assertSame(2, $recorder->getBufferedCount());

		$recorder->drain();

		self::assertSame([[self::T0, 1.0], [self::T0 + 8, 3.0], [self::T0 + 12, 4.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testFlushStoresAWaitingChangeOnceItsWindowIsOver(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		// The clock is frozen at NOW: the first window is over, the second is not
		$recorder->record($property, $this->state(1.0, self::NOW - 100));
		$recorder->record($property, $this->state(2.0, self::NOW - 95));
		$recorder->record(
			$this->property(id: self::OTHER_PROPERTY),
			$this->state(1.0, self::NOW - 5, id: self::OTHER_PROPERTY),
		);
		$recorder->record(
			$this->property(id: self::OTHER_PROPERTY),
			$this->state(2.0, self::NOW - 3, id: self::OTHER_PROPERTY),
		);

		self::assertSame(2, $recorder->getWaitingCount());

		$recorder->flush();

		self::assertSame(1, $recorder->getWaitingCount());
		self::assertSame([[self::NOW - 100, 1.0], [self::NOW - 95, 2.0]], $this->rows());
		self::assertSame([[self::NOW - 5, 1.0]], $this->rows(self::OTHER_PROPERTY));

		$recorder->drain();

		self::assertSame(0, $recorder->getWaitingCount());
		self::assertSame([[self::NOW - 5, 1.0], [self::NOW - 3, 2.0]], $this->rows(self::OTHER_PROPERTY));
	}

	/**
	 * @throws Throwable
	 */
	public function testMinIntervalOfZeroStoresEveryChange(): void
	{
		$recorder = $this->recorder(0, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 1));
		$recorder->record($property, $this->state(3.0, self::T0 + 1));

		self::assertSame(3, $recorder->getBufferedCount());
		self::assertSame(0, $recorder->getWaitingCount());
	}

	/**
	 * @throws Throwable
	 */
	public function testSampleTakesTheTimeOfTheStateElseTheClock(): void
	{
		$recorder = $this->recorder(0);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0));

		// A time the INT UNSIGNED column cannot hold is not a time
		$recorder->record($property, $this->state(3.0, 4_294_967_296));
		$recorder->record($property, $this->state(4.0, -1));

		self::assertSame([[self::T0, 1.0], [self::NOW, 4.0]], $this->rows());
	}

	/**
	 * The time of a state is an instant: its time zone does not move the stored epoch second
	 *
	 * @throws Throwable
	 */
	public function testTimeIsStoredInUtcWhateverTheTimeZoneOfTheState(): void
	{
		$recorder = $this->recorder(0);

		$state = new Tests\Fixtures\Dummy\ChannelPropertyState(
			Uuid\Uuid::fromString(self::PROPERTY),
			actualValue: 1.0,
			valid: true,
			updatedAt: new DateTimeImmutable('2020-04-01T12:00:00+05:00'),
		);

		$recorder->record($this->property(), $state);

		self::assertSame([[1_585_724_400, 1.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testBufferedWhileTheLoopRunsAndWrittenOnFlush(): void
	{
		$recorder = $this->recorder(10, true);

		$recorder->record($this->property(), $this->state(1.0, self::T0));

		self::assertSame(1, $recorder->getBufferedCount());
		self::assertSame([], $this->rows());

		$recorder->flush();

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame([[self::T0, 1.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testWrittenAtOnceWhileTheLoopDoesNotRun(): void
	{
		$recorder = $this->recorder(10, false);

		$recorder->record($this->property(), $this->state(1.0, self::T0));

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame([[self::T0, 1.0]], $this->rows());
	}

	/**
	 * The loop that is not running cannot flush later, so what waits for minInterval is written with the change
	 *
	 * @throws Throwable
	 */
	public function testNothingWaitsWhileTheLoopDoesNotRun(): void
	{
		$recorder = $this->recorder(10, false);

		$recorder->record($this->property(), $this->state(1.0, self::T0));
		$recorder->record($this->property(), $this->state(2.0, self::T0 + 3));

		self::assertSame(0, $recorder->getWaitingCount());
		self::assertSame([[self::T0, 1.0], [self::T0 + 3, 2.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testSampleOfTheSameSecondIsReplaced(): void
	{
		$property = $this->property();

		$first = $this->recorder(0);
		$first->record($property, $this->state(1.0, self::T0));

		// Another process, or the same one after a restart, stores the second again
		$second = $this->recorder(0);
		$second->record($property, $this->state(2.0, self::T0));

		self::assertSame([[self::T0, 2.0]], $this->rows());
	}

	/**
	 * Binding a float as a string would cut it to PHP's precision ini
	 *
	 * @throws Throwable
	 */
	public function testFloatIsStoredWithItsFullPrecision(): void
	{
		$recorder = $this->recorder(0);

		$recorder->record($this->property(), $this->state(0.1 + 0.2, self::T0));

		self::assertSame([[self::T0, 0.1 + 0.2]], $this->rows());
	}

	/**
	 * What a flush of a hundred properties, one sample each, costs the database is one statement
	 *
	 * @throws Throwable
	 */
	public function testHundredRowsAreWrittenByOneFlush(): void
	{
		$recorder = $this->recorder(10, true);

		for ($i = 0; $i < 100; $i++) {
			$id = Uuid\Uuid::uuid4()->toString();

			$recorder->record($this->property(id: $id), $this->state((float) $i, self::T0 + $i, id: $id));
		}

		self::assertSame(100, $recorder->getBufferedCount());

		$recorder->flush();

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame(100, $this->countRows());
	}

	/**
	 * More rows than one statement takes: the batch is split into statements of 500 rows, and every row arrives
	 *
	 * @throws Throwable
	 */
	public function testRowsBeyondOneStatementAreSplitBy500AndAllWritten(): void
	{
		$recorder = $this->recorder(0, true);
		$property = $this->property();

		for ($i = 0; $i < 1_201; $i++) {
			$recorder->record($property, $this->state((float) $i, self::T0 + $i));
		}

		$recorder->flush();

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame(1_201, $this->countRows());

		$connection = $this->getDb();
		self::assertInstanceOf(Tests\Support\ConnectionWrapper::class, $connection);

		$inserts = [];

		foreach ($connection->statements as $statement) {
			if (str_starts_with($statement, 'INSERT INTO ' . Entities\History\Sample::TABLE)) {
				$inserts[] = substr_count($statement, '(?, ?, ?)');
			}
		}

		self::assertSame([500, 500, 201], $inserts);
	}

	/**
	 * A repeat of the waiting value is the same change, so the time it first had stays
	 *
	 * @throws Throwable
	 */
	public function testRepeatOfTheWaitingValueKeepsTheEarlierTime(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 3));
		$recorder->record($property, $this->state(2.0, self::T0 + 6));

		self::assertSame(1, $recorder->getWaitingCount());

		$recorder->drain();

		self::assertSame([[self::T0, 1.0], [self::T0 + 3, 2.0]], $this->rows());
	}

	/**
	 * A repeat of the waiting value that arrives after its window is over finds it stored, and is a repeat of
	 * the stored value
	 *
	 * @throws Throwable
	 */
	public function testRepeatOfTheWaitingValueAfterItsWindowIsNotStoredAgain(): void
	{
		$recorder = $this->recorder(10, true);
		$property = $this->property();

		$recorder->record($property, $this->state(1.0, self::T0));
		$recorder->record($property, $this->state(2.0, self::T0 + 3));
		$recorder->record($property, $this->state(2.0, self::T0 + 12));

		self::assertSame(0, $recorder->getWaitingCount());

		$recorder->drain();

		self::assertSame([[self::T0, 1.0], [self::T0 + 3, 2.0]], $this->rows());
	}

	/**
	 * Database::transaction() nested in a transaction of the caller would roll the caller's back when the
	 * INSERT failed, so the recorder writes nothing while one is active and writes at the next flush
	 *
	 * @throws Throwable
	 */
	public function testNothingIsWrittenInsideATransactionOfTheCaller(): void
	{
		$recorder = $this->recorder(10, false);
		$connection = $this->getDb();

		$connection->beginTransaction();

		$recorder->record($this->property(), $this->state(1.0, self::T0));

		self::assertSame(1, $recorder->getBufferedCount());
		self::assertSame([], $this->rows());
		self::assertSame([], $this->logger->at('error'));
		self::assertFalse($connection->isRollbackOnly());

		$connection->commit();

		$recorder->flush();

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame([[self::T0, 1.0]], $this->rows());
	}

	/**
	 * A failed write keeps the rows, up to the bound: past it the oldest go, and the log says so
	 *
	 * @throws Throwable
	 */
	public function testBufferIsBoundedWhileTheDatabaseFails(): void
	{
		$recorder = $this->recorder(0, true);
		$property = $this->property();

		$this->breakTable();

		$total = Models\History\Recorder::MAX_BUFFERED_ROWS + 50;

		for ($i = 0; $i < $total; $i++) {
			$recorder->record($property, $this->state((float) $i, self::T0 + $i));
		}

		self::assertSame(Models\History\Recorder::MAX_BUFFERED_ROWS, $recorder->getBufferedCount());

		$recorder->flush();

		self::assertSame(Models\History\Recorder::MAX_BUFFERED_ROWS, $recorder->getBufferedCount());

		$errors = $this->logger->at('error');

		self::assertCount(1, $errors);
		self::assertSame('History samples could not be written', $errors[0]['message']);
		self::assertSame(50, $errors[0]['context']['dropped']);
		self::assertSame(Models\History\Recorder::MAX_BUFFERED_ROWS, $errors[0]['context']['buffered']);

		// The database is back: what was kept is written, the 50 oldest are gone
		$this->repairTable();

		$recorder->flush();

		$rows = $this->rows();

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertCount(Models\History\Recorder::MAX_BUFFERED_ROWS, $rows);
		self::assertSame([self::T0 + 50, 50.0], $rows[0]);
		self::assertSame(
			[self::T0 + $total - 1, (float) ($total - 1)],
			$rows[Models\History\Recorder::MAX_BUFFERED_ROWS - 1],
		);
	}

	/**
	 * A failed write does not throw into the state write that raised it, and the next write retries
	 *
	 * @throws Throwable
	 */
	public function testFailedWriteIsLoggedAndRetried(): void
	{
		$recorder = $this->recorder(10, false);
		$property = $this->property();

		$this->breakTable();

		$recorder->record($property, $this->state(1.0, self::T0));

		self::assertSame(1, $recorder->getBufferedCount());
		self::assertCount(1, $this->logger->at('error'));

		$this->repairTable();

		$recorder->record($property, $this->state(2.0, self::T0 + 60));

		self::assertSame(0, $recorder->getBufferedCount());
		self::assertSame([[self::T0, 1.0], [self::T0 + 60, 2.0]], $this->rows());
	}

	/**
	 * @throws Throwable
	 */
	public function testRowsDroppedBeyondTheBoundAreLoggedOnceWritten(): void
	{
		$recorder = $this->recorder(0, true);
		$property = $this->property();

		for ($i = 0; $i <= Models\History\Recorder::MAX_BUFFERED_ROWS; $i++) {
			$recorder->record($property, $this->state((float) $i, self::T0 + $i));
		}

		$recorder->flush();

		$warnings = $this->logger->at('warning');

		self::assertCount(1, $warnings);
		self::assertSame(1, $warnings[0]['context']['dropped']);
		self::assertSame([], $this->logger->at('error'));
	}

	/**
	 * @throws Throwable
	 */
	private function breakTable(): void
	{
		$this->getDb()->executeStatement(
			sprintf('RENAME TABLE %s TO %s_away', Entities\History\Sample::TABLE, Entities\History\Sample::TABLE),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function repairTable(): void
	{
		$this->getDb()->executeStatement(
			sprintf('RENAME TABLE %s_away TO %s', Entities\History\Sample::TABLE, Entities\History\Sample::TABLE),
		);
	}

}
