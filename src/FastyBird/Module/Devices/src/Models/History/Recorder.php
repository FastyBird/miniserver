<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

use Doctrine\DBAL;
use FastyBird\Core\EventLoop;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\States;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Ramsey\Uuid;
use Throwable;
use function abs;
use function array_chunk;
use function array_fill;
use function array_slice;
use function count;
use function implode;
use function is_finite;
use function is_float;
use function is_int;
use function sprintf;
use function var_export;

/**
 * Records the value of a property each time its state changes, for the property value history
 *
 * A change is stored as a sample, a number in the unit the property reports it in, or NULL, a gap,
 * for a property whose state is not valid. What is recorded, and how often:
 *
 * - only a numeric, boolean or switch property, and never a mapped one: its parent is recorded, and the
 *   events of the child would double the sample. A boolean is stored as 0 or 1, a switch as 1 for
 *   `switch_on` and 0 for `switch_off`; `switch_toggle` is a command, not a value;
 * - a value equal to the last one stored is not stored, a gap after a gap neither, and a numeric change
 *   smaller than the property's step is not stored either. The last one stored is remembered in memory, so
 *   the first change a process sees for a property is stored even when it repeats the row of an earlier run;
 * - a change within minInterval seconds of the last one stored waits, and a later change replaces it, with
 *   its own value and time. That bounds how often a property is written, at most one write per property in
 *   each window of minInterval seconds, and a spike shorter than that is not kept. It does not space the
 *   samples: the waiting change keeps its own time, so two stored samples can be closer than minInterval
 *   seconds. A change that comes back to the last stored value drops the waiting one, and a repeat of the
 *   waiting value leaves it as it is, with the earlier time;
 * - the time of a sample is the `updated_at` of the state, else the clock, in UTC epoch seconds.
 *
 * Samples are collected in a buffer and written by flush() in one multi-row INSERT inside a transaction.
 * While the event loop runs the caller flushes on a timer. While it does not (php-fpm, a console command)
 * nothing would flush later, so a sample is written at once. A failed write keeps the rows for the next
 * flush, up to MAX_BUFFERED_ROWS: past that the oldest are dropped, and the log says so. Recording never
 * throws, because a state that was just written must not fail on its history.
 *
 * It does not write inside a transaction of the caller. Database::transaction() nested in one marks the outer
 * transaction rollback-only when its INSERT fails, which would fail the caller for a history error. State
 * writes do not run inside a transaction today; if one ever does, the rows stay buffered and the next flush
 * outside it writes them.
 */
final class Recorder
{

	public const int MAX_BUFFERED_ROWS = 10_000;

	// 3 parameters a row, far below the 65,535 a prepared statement takes
	private const int ROWS_PER_STATEMENT = 500;

	// The largest value of INT UNSIGNED, the type of the time columns
	private const int MAX_TIME = 4_294_967_295;

	/** @var array<string, array{time: int, value: float|null}> */
	private array $stored = [];

	/** @var array<string, array{id: Uuid\UuidInterface, time: int, value: float|null, since: int}> */
	private array $waiting = [];

	/** @var list<array{Uuid\UuidInterface, int, float|null}> */
	private array $buffer = [];

	private int $dropped = 0;

	public function __construct(
		private readonly DBAL\Connection $connection,
		private readonly Helpers\Database $database,
		private readonly EventLoop\Status $eventLoopStatus,
		private readonly ClockInterface $clock,
		private readonly int $minInterval,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function record(Documents\Property $property, States\Property $state): void
	{
		if (
			$property instanceof Documents\Devices\Properties\Mapped
			|| $property instanceof Documents\Channels\Properties\Mapped
		) {
			return;
		}

		$dataType = $property->getDataType();

		if (!self::isRecordable($dataType)) {
			return;
		}

		if ($state->isValid()) {
			$value = self::toNumber($dataType, $state->getActualValue());

			// The state holds no value yet, or one that is not a number the history can keep
			if ($value === null) {
				return;
			}
		} else {
			$value = null;
		}

		$this->change(
			$property->getId(),
			$this->timeOf($state),
			$value,
			$dataType->isInteger() || $dataType === ValuesTypes\DataType::FLOAT ? $property->getStep() : null,
		);

		if (!$this->eventLoopStatus->running) {
			$this->drain();
		}
	}

	/**
	 * Writes what is buffered, and moves a waiting change whose minInterval has passed to the buffer first
	 */
	public function flush(): void
	{
		$now = $this->clock->now()->getTimestamp();

		foreach ($this->waiting as $key => $waiting) {
			if ($now - $waiting['since'] >= $this->minInterval) {
				$this->store($key, $waiting['id'], $waiting['time'], $waiting['value']);

				unset($this->waiting[$key]);
			}
		}

		$this->write();
	}

	/**
	 * Writes everything there is, waiting changes included: the process is about to stop, or nothing
	 * would flush later
	 */
	public function drain(): void
	{
		foreach ($this->waiting as $key => $waiting) {
			$this->store($key, $waiting['id'], $waiting['time'], $waiting['value']);
		}

		$this->waiting = [];

		$this->write();
	}

	public function getBufferedCount(): int
	{
		return count($this->buffer);
	}

	public function getWaitingCount(): int
	{
		return count($this->waiting);
	}

	private function change(Uuid\UuidInterface $id, int $time, float|null $value, int|float|null $step): void
	{
		$key = $id->toString();

		$waiting = $this->waiting[$key] ?? null;

		// The window of the waiting change is over: it is stored as it is, and this change is
		// weighed against it
		if ($waiting !== null && $time - $waiting['since'] >= $this->minInterval) {
			$this->store($key, $waiting['id'], $waiting['time'], $waiting['value']);

			unset($this->waiting[$key]);

			$waiting = null;
		}

		// A repeat of the waiting value is the same change: it keeps the time it first had
		if ($waiting !== null && $waiting['value'] === $value) {
			return;
		}

		$last = $this->stored[$key] ?? null;

		if ($last !== null && !self::isChange($last['value'], $value, $step)) {
			// The value is back at the stored one, so what waited is a spike that is not kept
			unset($this->waiting[$key]);

			return;
		}

		if ($last !== null && $this->minInterval > 0 && $time - $last['time'] < $this->minInterval) {
			$this->waiting[$key] = [
				'id' => $id,
				'time' => $time,
				'value' => $value,
				'since' => $waiting['since'] ?? $last['time'],
			];

			return;
		}

		unset($this->waiting[$key]);

		$this->store($key, $id, $time, $value);
	}

	private function store(string $key, Uuid\UuidInterface $id, int $time, float|null $value): void
	{
		$this->stored[$key] = ['time' => $time, 'value' => $value];

		$this->buffer[] = [$id, $time, $value];

		if (count($this->buffer) > self::MAX_BUFFERED_ROWS) {
			$this->dropOldest();
		}
	}

	private function dropOldest(): void
	{
		$excess = count($this->buffer) - self::MAX_BUFFERED_ROWS;

		$this->buffer = array_slice($this->buffer, $excess);
		$this->dropped += $excess;
	}

	private function write(): void
	{
		if ($this->buffer === []) {
			return;
		}

		if ($this->connection->isTransactionActive()) {
			$this->logger->debug(
				'History samples are kept for a later flush, a transaction is active',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'history-recorder',
					'buffered' => count($this->buffer),
				],
			);

			return;
		}

		$rows = $this->buffer;

		try {
			$this->database->transaction(function () use ($rows): void {
				foreach (array_chunk($rows, self::ROWS_PER_STATEMENT) as $chunk) {
					$this->insert($chunk);
				}
			});
		} catch (Throwable $ex) {
			// The rows stay for the next flush, and store() keeps them within the bound
			$this->logger->error(
				'History samples could not be written',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'history-recorder',
					'exception' => Logging\Logger::buildException($ex),
					'buffered' => count($this->buffer),
					'dropped' => $this->dropped,
				],
			);

			$this->dropped = 0;

			return;
		}

		$this->buffer = array_slice($this->buffer, count($rows));

		if ($this->dropped > 0) {
			$this->logger->warning(
				'History samples were dropped while the database could not be written',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'history-recorder',
					'dropped' => $this->dropped,
				],
			);

			$this->dropped = 0;
		}
	}

	/**
	 * @param list<array{Uuid\UuidInterface, int, float|null}> $rows
	 *
	 * @throws DBAL\Exception
	 */
	private function insert(array $rows): void
	{
		$parameters = [];
		$types = [];

		foreach ($rows as [$id, $time, $value]) {
			$parameters[] = $id->getBytes();
			$types[] = DBAL\ParameterType::BINARY;

			$parameters[] = $time;
			$types[] = DBAL\ParameterType::INTEGER;

			// A float bound as a string is cut to PHP's `precision` digits, var_export() keeps all of it
			$parameters[] = $value === null ? null : var_export($value, true);
			$types[] = $value === null ? DBAL\ParameterType::NULL : DBAL\ParameterType::STRING;
		}

		$this->connection->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, sampled_at, value) VALUES %s ON DUPLICATE KEY UPDATE value = VALUES(value)',
				Entities\History\Sample::TABLE,
				implode(', ', array_fill(0, count($rows), '(?, ?, ?)')),
			),
			$parameters,
			$types,
		);
	}

	private function timeOf(States\Property $state): int
	{
		$time = $state->getUpdatedAt()?->getTimestamp();

		if ($time === null || $time < 0 || $time > self::MAX_TIME) {
			return $this->clock->now()->getTimestamp();
		}

		return $time;
	}

	private static function isRecordable(ValuesTypes\DataType $dataType): bool
	{
		return $dataType->isInteger()
			|| $dataType === ValuesTypes\DataType::FLOAT
			|| $dataType === ValuesTypes\DataType::BOOLEAN
			|| $dataType === ValuesTypes\DataType::SWITCH;
	}

	private static function toNumber(ValuesTypes\DataType $dataType, mixed $value): float|null
	{
		if ($dataType === ValuesTypes\DataType::BOOLEAN) {
			return match ($value) {
				true => 1.0,
				false => 0.0,
				default => null,
			};
		}

		if ($dataType === ValuesTypes\DataType::SWITCH) {
			return match ($value) {
				Payloads\Switcher::ON => 1.0,
				Payloads\Switcher::OFF => 0.0,
				default => null,
			};
		}

		if (is_int($value)) {
			return (float) $value;
		}

		// A double column holds neither NaN nor an infinity
		return is_float($value) && is_finite($value) ? $value : null;
	}

	private static function isChange(float|null $last, float|null $value, int|float|null $step): bool
	{
		if ($last === null || $value === null) {
			return $last !== $value;
		}

		if ($last === $value) {
			return false;
		}

		return $step === null || $step <= 0 || abs($value - $last) >= $step;
	}

}
