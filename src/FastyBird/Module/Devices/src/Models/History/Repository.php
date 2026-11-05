<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

use DateTimeImmutable;
use DateTimeInterface;
use DivisionByZeroError;
use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid;
use function array_map;
use function count;
use function intdiv;
use function is_numeric;
use function max;
use function sprintf;

/**
 * Reads a property's value history as a series
 *
 * The three tiers hold the same history at different widths: the samples as they were stored, the 5-minute
 * rollups and the 1-hour rollups. The wider ones live longer, so which one answers depends on the period and on
 * how many points the caller wants:
 *
 * - the bucket is the period divided by the points, rounded up, in seconds;
 * - the tier is the widest one that is not wider than the bucket and still holds the start of the period;
 * - when there is none, the narrowest one that holds the start of the period, and when no tier holds it, the
 *   hours, which reach the furthest.
 *
 * The period is half open, from the start up to, not including, the end. The points are buckets counted from
 * the start. A rollup tier answers from the rollups that exist, so the newest part of a period, the hour or the
 * 5 minutes the compactor has not reached yet, is missing from it.
 *
 * It reads the property it is given. Resolving a mapped property to its parent is the caller's business.
 */
final class Repository
{

	private const int DAY = 86_400;

	public function __construct(
		private readonly DBAL\Connection $connection,
		private readonly Helpers\Database $database,
		private readonly ClockInterface $clock,
		private readonly int $samplesDays,
		private readonly int $fiveMinutesDays,
		private readonly int $hoursDays,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 */
	public function tierFor(DateTimeInterface $from, DateTimeInterface $to, int $points): Types\HistoryTier
	{
		return $this->choose($from->getTimestamp(), self::bucketOf($from, $to, $points));
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function find(
		Uuid\UuidInterface $property,
		DateTimeInterface $from,
		DateTimeInterface $to,
		int $points = 500,
	): Series
	{
		$bucket = self::bucketOf($from, $to, $points);
		$start = $from->getTimestamp();
		$end = $to->getTimestamp();
		$tier = $this->choose($start, $bucket);

		$buckets = $this->database->query(
			fn (): array => $tier === Types\HistoryTier::SAMPLES
				? $this->fromSamples($property, $start, $end, $bucket)
				: $this->fromRollups(
					$property,
					$tier === Types\HistoryTier::HOURS ? Types\HistoryResolution::HOUR : Types\HistoryResolution::FIVE_MINUTES,
					$start,
					$end,
					$bucket,
				),
		);

		return new Series(
			DateTimeImmutable::createFromInterface($from),
			DateTimeImmutable::createFromInterface($to),
			$bucket,
			$tier,
			array_map(static fn (Bucket $bucket): int => $bucket->time, $buckets),
			array_map(static fn (Bucket $bucket): float|null => $bucket->min, $buckets),
			array_map(static fn (Bucket $bucket): float|null => $bucket->max, $buckets),
			array_map(static fn (Bucket $bucket): float|null => $bucket->avg, $buckets),
			array_map(static fn (Bucket $bucket): float|null => $bucket->last, $buckets),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 */
	private static function bucketOf(DateTimeInterface $from, DateTimeInterface $to, int $points): int
	{
		$span = $to->getTimestamp() - $from->getTimestamp();

		if ($span <= 0) {
			throw new DevicesExceptions\InvalidArgument('The end of a history period has to be after its start');
		}

		if ($points < 1) {
			throw new DevicesExceptions\InvalidArgument('A history series needs at least one point');
		}

		return max(1, intdiv($span + $points - 1, $points));
	}

	private function choose(int $start, int $bucket): Types\HistoryTier
	{
		$now = $this->clock->now()->getTimestamp();

		// Widest first: the hours, the 5-minute rollups, the samples
		$tiers = [
			[Types\HistoryTier::HOURS, Types\HistoryResolution::HOUR->value, $this->hoursDays],
			[Types\HistoryTier::FIVE_MINUTES, Types\HistoryResolution::FIVE_MINUTES->value, $this->fiveMinutesDays],
			[Types\HistoryTier::SAMPLES, 1, $this->samplesDays],
		];

		$covering = [];

		foreach ($tiers as [$tier, $width, $days]) {
			if ($start >= $now - $days * self::DAY) {
				if ($width <= $bucket) {
					return $tier;
				}

				$covering[] = $tier;
			}
		}

		return $covering === [] ? Types\HistoryTier::HOURS : $covering[count($covering) - 1];
	}

	/**
	 * @return list<Bucket>
	 *
	 * @throws DBAL\Exception
	 * @throws DivisionByZeroError
	 */
	private function fromSamples(Uuid\UuidInterface $property, int $start, int $end, int $bucket): array
	{
		$id = $property->getBytes();

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

		return Aggregator::samples(
			$start,
			$end,
			$bucket,
			$carry === false ? null : [self::number($carry['value'])],
			$samples,
		);
	}

	/**
	 * @return list<Bucket>
	 *
	 * @throws DBAL\Exception
	 * @throws DivisionByZeroError
	 */
	private function fromRollups(
		Uuid\UuidInterface $property,
		Types\HistoryResolution $resolution,
		int $start,
		int $end,
		int $bucket,
	): array
	{
		$rows = [];

		foreach (
			$this->connection->fetchAllAssociative(
				sprintf(
					'SELECT bucket_start, value_min, value_max, value_avg, value_last, samples FROM %s'
					. ' WHERE property_id = ? AND resolution = ? AND bucket_start >= ? AND bucket_start < ?'
					. ' ORDER BY bucket_start',
					Entities\History\Rollup::TABLE,
				),
				[
					$property->getBytes(),
					$resolution->value,
					intdiv($start, $resolution->value) * $resolution->value,
					$end,
				],
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

		return Aggregator::combine($start, $bucket, $rows);
	}

	private static function number(mixed $value): float|null
	{
		return is_numeric($value) ? (float) $value : null;
	}

}
