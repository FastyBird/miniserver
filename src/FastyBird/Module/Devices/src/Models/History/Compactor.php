<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

use DivisionByZeroError;
use Doctrine\DBAL;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Types;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Ramsey\Uuid;
use Throwable;
use function array_chunk;
use function array_fill;
use function count;
use function implode;
use function intdiv;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function sprintf;
use function var_export;

/**
 * Rolls the property value history up and lets it expire
 *
 * One run, for each property that has history:
 *
 * - **5-minute rollups.** Every bucket from the last one stored (or the property's first sample) up to the
 *   newest that is complete is built from the samples and upserted. A bucket is complete once the time left in
 *   it, plus `settle` seconds, has passed: so no process still holds unflushed samples for it. The last bucket
 *   stored is built again, which is what makes a run safe to repeat and picks up a late sample.
 * - **1-hour rollups**, built the same way from the 5-minute ones.
 * - **Retention.** Samples, 5-minute rollups and 1-hour rollups older than their retention are deleted, with
 *   one exception: a property's newest sample stays. A value that has not changed for longer than the retention
 *   of the samples is still the value of the property, and the next rollup needs it to know that. A property
 *   keeps getting rollups while it exists, one row for each bucket, and they age out with their retention.
 * - **Dead properties.** The history of a property that no longer exists in the connector, device or channel
 *   property tables, samples and rollups alike, is deleted, in batches. That is what lets the newest sample of a
 *   deleted property go, and with it the rollups it would otherwise keep producing.
 *
 * Every property is one transaction, so a failure leaves the others done and is the next run's to repeat. The
 * time is the clock's, in UTC epoch seconds, and the buckets are aligned to UTC.
 */
final class Compactor
{

	// A day of 5-minute buckets, and 30 days of hours: what one query reads and one statement writes
	private const int FIVE_MINUTES_PER_CHUNK = 288;

	private const int HOURS_PER_CHUNK = 720;

	// 8 parameters a row
	private const int ROWS_PER_STATEMENT = 400;

	private const int DAY = 86_400;

	// Rows one DELETE removes when the history of a dead property is purged
	private const int PURGE_BATCH = 10_000;

	// The tables a property id can live in
	private const array PROPERTY_TABLES = [
		'fb_devices_module_connectors_properties',
		'fb_devices_module_devices_properties',
		'fb_devices_module_channels_properties',
	];

	public function __construct(
		private readonly DBAL\Connection $connection,
		private readonly Helpers\Database $database,
		private readonly ClockInterface $clock,
		private readonly int $settle,
		private readonly int $samplesDays,
		private readonly int $fiveMinutesDays,
		private readonly int $hoursDays,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * Never throws: what cannot be done is logged and counted
	 */
	public function compact(): Compaction
	{
		$now = $this->clock->now()->getTimestamp();

		try {
			$properties = $this->database->query(fn (): array => $this->properties());
		} catch (Throwable $ex) {
			$this->logger->error(
				'History properties could not be listed',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'history-compactor',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			return new Compaction(0, 1, 0, 0, 0, 0, 0, 0);
		}

		$done = 0;
		$failed = 0;
		$totals = [0, 0, 0, 0, 0, 0];

		foreach ($properties as $property) {
			try {
				$counts = $this->database->transaction(fn (): array => $this->compactProperty($property, $now));
			} catch (Throwable $ex) {
				$failed++;

				$this->logger->error(
					'History of a property could not be compacted',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'history-compactor',
						'property' => $property->toString(),
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				continue;
			}

			$done++;

			foreach ($counts as $index => $count) {
				$totals[$index] += $count;
			}
		}

		return new Compaction($done, $failed, $totals[0], $totals[1], $totals[2], $totals[3], $totals[4], $totals[5]);
	}

	/**
	 * @return list<Uuid\UuidInterface>
	 *
	 * @throws DBAL\Exception
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function properties(): array
	{
		$properties = [];

		foreach (
			$this->connection->fetchFirstColumn(
				sprintf(
					'SELECT DISTINCT property_id FROM %s UNION SELECT DISTINCT property_id FROM %s',
					Entities\History\Sample::TABLE,
					Entities\History\Rollup::TABLE,
				),
			) as $bytes
		) {
			if (is_string($bytes)) {
				$properties[] = Uuid\Uuid::fromBytes($bytes);
			}
		}

		return $properties;
	}

	/**
	 * @return array{int, int, int, int, int, int} 5-minute and 1-hour rollups written, samples, 5-minute and 1-hour
	 *                                             rollups expired, rows purged
	 *
	 * @throws DBAL\Exception
	 * @throws DivisionByZeroError
	 */
	private function compactProperty(Uuid\UuidInterface $property, int $now): array
	{
		$id = $property->getBytes();

		if (!$this->exists($id)) {
			return [0, 0, 0, 0, 0, $this->purge($id)];
		}

		$fiveMinutes = $this->fiveMinutes($id, $now);
		$hours = $this->hours($id, $now);

		return [$fiveMinutes, $hours, ...$this->expire($id, $now), 0];
	}

	/**
	 * @throws DBAL\Exception
	 */
	private function exists(string $id): bool
	{
		$selects = [];

		foreach (self::PROPERTY_TABLES as $table) {
			$selects[] = sprintf('SELECT 1 FROM %s WHERE property_id = ?', $table);
		}

		return $this->connection->fetchOne(
			implode(' UNION ALL ', $selects) . ' LIMIT 1',
			[$id, $id, $id],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::BINARY, DBAL\ParameterType::BINARY],
		) !== false;
	}

	/**
	 * Deletes everything recorded for a property that does not exist, in batches
	 *
	 * @throws DBAL\Exception
	 */
	private function purge(string $id): int
	{
		$purged = 0;

		foreach ([Entities\History\Sample::TABLE, Entities\History\Rollup::TABLE] as $table) {
			do {
				$deleted = (int) $this->connection->executeStatement(
					sprintf('DELETE FROM %s WHERE property_id = ? LIMIT %d', $table, self::PURGE_BATCH),
					[$id],
					[DBAL\ParameterType::BINARY],
				);

				$purged += $deleted;
			} while ($deleted === self::PURGE_BATCH);
		}

		return $purged;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DivisionByZeroError
	 */
	private function fiveMinutes(string $id, int $now): int
	{
		$width = Types\HistoryResolution::FIVE_MINUTES->value;

		$horizon = intdiv($now - $this->settle, $width) * $width;
		$last = $this->scalar(
			sprintf(
				'SELECT MAX(bucket_start) FROM %s WHERE property_id = ? AND resolution = ?',
				Entities\History\Rollup::TABLE,
			),
			$id,
			$width,
		);

		if ($last === null) {
			$first = $this->scalar(
				sprintf('SELECT MIN(sampled_at) FROM %s WHERE property_id = ?', Entities\History\Sample::TABLE),
				$id,
			);

			if ($first === null) {
				return 0;
			}

			$last = intdiv($first, $width) * $width;
		}

		// A bucket that would be expired as soon as it was written is not written: the first one is the first whose
		// start is not before the retention's cutoff
		$start = max($last, self::roundUp($now - $this->fiveMinutesDays * self::DAY, $width));
		$written = 0;

		while ($start < $horizon) {
			$end = min($horizon, $start + self::FIVE_MINUTES_PER_CHUNK * $width);

			$carry = $this->connection->fetchAssociative(
				sprintf(
					'SELECT value FROM %s WHERE property_id = ? AND sampled_at < ? ORDER BY sampled_at DESC LIMIT 1',
					Entities\History\Sample::TABLE,
				),
				[$id, $start],
				[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER],
			);

			$samples = [];

			foreach (
				$this->connection->fetchAllAssociative(
					sprintf(
						'SELECT sampled_at, value FROM %s WHERE property_id = ? AND sampled_at >= ? AND sampled_at < ?'
						. ' ORDER BY sampled_at',
						Entities\History\Sample::TABLE,
					),
					[$id, $start, $end],
					[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER, DBAL\ParameterType::INTEGER],
				) as $row
			) {
				$samples[] = [(int) self::number($row['sampled_at']), self::number($row['value'])];
			}

			$written += $this->upsert(
				$id,
				Types\HistoryResolution::FIVE_MINUTES,
				Aggregator::samples(
					$start,
					$end,
					$width,
					$carry === false ? null : [self::number($carry['value'])],
					$samples,
				),
			);

			$start = $end;
		}

		return $written;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DivisionByZeroError
	 */
	private function hours(string $id, int $now): int
	{
		$width = Types\HistoryResolution::HOUR->value;
		$source = Types\HistoryResolution::FIVE_MINUTES->value;

		// The hours that end at or before the newest complete 5-minute bucket
		$horizon = intdiv(intdiv($now - $this->settle, $source) * $source, $width) * $width;
		$last = $this->scalar(
			sprintf(
				'SELECT MAX(bucket_start) FROM %s WHERE property_id = ? AND resolution = ?',
				Entities\History\Rollup::TABLE,
			),
			$id,
			$width,
		);

		if ($last === null) {
			$first = $this->scalar(
				sprintf(
					'SELECT MIN(bucket_start) FROM %s WHERE property_id = ? AND resolution = ?',
					Entities\History\Rollup::TABLE,
				),
				$id,
				$source,
			);

			if ($first === null) {
				return 0;
			}

			$last = intdiv($first, $width) * $width;
		}

		$start = max($last, self::roundUp($now - $this->hoursDays * self::DAY, $width));
		$written = 0;

		while ($start < $horizon) {
			$end = min($horizon, $start + self::HOURS_PER_CHUNK * $width);

			$rows = [];

			foreach (
				$this->connection->fetchAllAssociative(
					sprintf(
						'SELECT bucket_start, value_min, value_max, value_avg, value_last, samples FROM %s'
						. ' WHERE property_id = ? AND resolution = ? AND bucket_start >= ? AND bucket_start < ?'
						. ' ORDER BY bucket_start',
						Entities\History\Rollup::TABLE,
					),
					[$id, $source, $start, $end],
					[
						DBAL\ParameterType::BINARY,
						DBAL\ParameterType::INTEGER,
						DBAL\ParameterType::INTEGER,
						DBAL\ParameterType::INTEGER,
					],
				) as $row
			) {
				$rows[] = new Bucket(
					(int) self::number($row['bucket_start']),
					self::number($row['value_min']),
					self::number($row['value_max']),
					self::number($row['value_avg']),
					self::number($row['value_last']),
					(int) self::number($row['samples']),
				);
			}

			$written += $this->upsert($id, Types\HistoryResolution::HOUR, Aggregator::combine($start, $width, $rows));

			$start = $end;
		}

		return $written;
	}

	/**
	 * @return array{int, int, int}
	 *
	 * @throws DBAL\Exception
	 */
	private function expire(string $id, int $now): array
	{
		$cutoff = $now - $this->samplesDays * self::DAY;
		$newest = $this->scalar(
			sprintf('SELECT MAX(sampled_at) FROM %s WHERE property_id = ?', Entities\History\Sample::TABLE),
			$id,
		);

		if ($newest !== null) {
			$cutoff = min($cutoff, $newest);
		}

		$samples = $this->connection->executeStatement(
			sprintf('DELETE FROM %s WHERE property_id = ? AND sampled_at < ?', Entities\History\Sample::TABLE),
			[$id, $cutoff],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER],
		);

		return [
			(int) $samples,
			$this->expireRollups($id, Types\HistoryResolution::FIVE_MINUTES, $now - $this->fiveMinutesDays * self::DAY),
			$this->expireRollups($id, Types\HistoryResolution::HOUR, $now - $this->hoursDays * self::DAY),
		];
	}

	/**
	 * @throws DBAL\Exception
	 */
	private function expireRollups(string $id, Types\HistoryResolution $resolution, int $cutoff): int
	{
		return (int) $this->connection->executeStatement(
			sprintf(
				'DELETE FROM %s WHERE property_id = ? AND resolution = ? AND bucket_start < ?',
				Entities\History\Rollup::TABLE,
			),
			[$id, $resolution->value, $cutoff],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER, DBAL\ParameterType::INTEGER],
		);
	}

	/**
	 * @param list<Bucket> $buckets
	 *
	 * @throws DBAL\Exception
	 */
	private function upsert(string $id, Types\HistoryResolution $resolution, array $buckets): int
	{
		foreach (array_chunk($buckets, self::ROWS_PER_STATEMENT) as $chunk) {
			$parameters = [];
			$types = [];

			foreach ($chunk as $bucket) {
				$parameters[] = $id;
				$types[] = DBAL\ParameterType::BINARY;

				$parameters[] = $resolution->value;
				$types[] = DBAL\ParameterType::INTEGER;

				$parameters[] = $bucket->time;
				$types[] = DBAL\ParameterType::INTEGER;

				foreach ([$bucket->min, $bucket->max, $bucket->avg, $bucket->last] as $value) {
					// A float bound as a string is cut to PHP's `precision` digits, var_export() keeps all of it
					$parameters[] = $value === null ? null : var_export($value, true);
					$types[] = $value === null ? DBAL\ParameterType::NULL : DBAL\ParameterType::STRING;
				}

				$parameters[] = $bucket->samples;
				$types[] = DBAL\ParameterType::INTEGER;
			}

			$this->connection->executeStatement(
				sprintf(
					'INSERT INTO %s (property_id, resolution, bucket_start, value_min, value_max, value_avg, value_last, samples)'
					. ' VALUES %s ON DUPLICATE KEY UPDATE value_min = VALUES(value_min), value_max = VALUES(value_max),'
					. ' value_avg = VALUES(value_avg), value_last = VALUES(value_last), samples = VALUES(samples)',
					Entities\History\Rollup::TABLE,
					implode(', ', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?)')),
				),
				$parameters,
				$types,
			);
		}

		return count($buckets);
	}

	/**
	 * @throws DBAL\Exception
	 */
	private function scalar(string $sql, string $id, int|null $resolution = null): int|null
	{
		$value = $this->connection->fetchOne(
			$sql,
			$resolution === null ? [$id] : [$id, $resolution],
			$resolution === null ? [DBAL\ParameterType::BINARY] : [DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER],
		);

		return is_numeric($value) ? (int) $value : null;
	}

	/**
	 * The first multiple of $width at or after $time
	 *
	 * @throws DivisionByZeroError
	 */
	private static function roundUp(int $time, int $width): int
	{
		return intdiv($time + $width - 1, $width) * $width;
	}

	private static function number(mixed $value): float|null
	{
		return is_numeric($value) ? (float) $value : null;
	}

}
