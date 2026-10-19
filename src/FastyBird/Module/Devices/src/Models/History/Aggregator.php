<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

use DivisionByZeroError;
use function array_sum;
use function count;
use function intdiv;
use function is_finite;
use function max;
use function min;

/**
 * The arithmetic of the property value history: samples into buckets, and buckets into wider ones
 *
 * The compactor builds the rollups with it and the repository builds a series with it, so a rollup and a
 * series read from samples are the same numbers.
 */
final class Aggregator
{

	/**
	 * Samples into buckets of $width seconds, from $start up to $end
	 *
	 * A sample holds until the next one, so a bucket is weighed by how long each value held in it, and the value
	 * that held when the bucket opened counts from its first second ($carry, the last sample before $start). A
	 * bucket with no sample in it still has that value, and so has a row. A gap (a null value) weighs nothing, and
	 * a bucket of nothing else has no values. A bucket in which the history knows no value yet, nothing before it
	 * and no sample in it, is not returned.
	 *
	 * @param array{float|null}|null $carry null when no sample came before $start, else the value of the last one
	 * @param list<array{int, float|null}> $samples [time, value] in time order, from $start up to, not including, $end
	 *
	 * @return list<Bucket>
	 *
	 * @throws DivisionByZeroError
	 */
	public static function samples(int $start, int $end, int $width, array|null $carry, array $samples): array
	{
		$buckets = [];
		$held = $carry;
		$next = 0;
		$total = count($samples);

		for ($bucketStart = $start; $bucketStart < $end; $bucketStart += $width) {
			$bucketEnd = min($bucketStart + $width, $end);
			$cursor = $bucketStart;
			$count = 0;
			$weighed = ['sum' => 0.0, 'time' => 0, 'min' => null, 'max' => null, 'segments' => []];

			while ($next < $total && $samples[$next][0] < $bucketEnd) {
				[$time, $value] = $samples[$next];

				if ($held !== null && $time > $cursor) {
					self::weigh($weighed, $held[0], $time - $cursor);
				}

				$cursor = $time;
				$held = [$value];
				$count++;
				$next++;
			}

			if ($held === null) {
				continue;
			}

			if ($bucketEnd > $cursor) {
				self::weigh($weighed, $held[0], $bucketEnd - $cursor);
			}

			$buckets[] = new Bucket(
				$bucketStart,
				$weighed['min'],
				$weighed['max'],
				self::average($weighed),
				$held[0],
				$count,
			);
		}

		return $buckets;
	}

	/**
	 * Buckets into buckets of $width seconds counted from $start
	 *
	 * The minimum and the maximum are the extremes of the rows, the average is the mean of their averages, each
	 * row weighing the same, and the last value is the last row's. A row that starts before $start belongs to the
	 * first bucket.
	 *
	 * @param list<Bucket> $rows in time order
	 *
	 * @return list<Bucket>
	 *
	 * @throws DivisionByZeroError
	 */
	public static function combine(int $start, int $width, array $rows): array
	{
		$groups = [];

		foreach ($rows as $row) {
			$groups[intdiv(max(0, $row->time - $start), $width)][] = $row;
		}

		$buckets = [];

		foreach ($groups as $index => $group) {
			$min = null;
			$max = null;
			$averages = [];
			$samples = 0;

			foreach ($group as $row) {
				$samples += $row->samples;

				if ($row->min !== null) {
					$min = $min === null ? $row->min : min($min, $row->min);
				}

				if ($row->max !== null) {
					$max = $max === null ? $row->max : max($max, $row->max);
				}

				if ($row->avg !== null) {
					$averages[] = $row->avg;
				}
			}

			$buckets[] = new Bucket(
				$start + $index * $width,
				$min,
				$max,
				self::mean($averages),
				$group[count($group) - 1]->last,
				$samples,
			);
		}

		return $buckets;
	}

	/**
	 * @param array{sum: float, time: int, min: float|null, max: float|null, segments: list<array{float, int}>} $weighed
	 */
	private static function weigh(array &$weighed, float|null $value, int $duration): void
	{
		if ($value === null) {
			return;
		}

		$weighed['sum'] += $value * $duration;
		$weighed['time'] += $duration;
		$weighed['segments'][] = [$value, $duration];
		$weighed['min'] = $weighed['min'] === null ? $value : min($weighed['min'], $value);
		$weighed['max'] = $weighed['max'] === null ? $value : max($weighed['max'], $value);
	}

	/**
	 * The time-weighted average. The sum of the products is the plain way, and the one whose floating point result
	 * a reader expects (10 for a third, 20 for a third and 30 for a third is exactly 20). Values near the largest
	 * double make it overflow to infinity, which a double column refuses, so then each value is weighed by its
	 * share of the time instead: a convex combination, which cannot leave the range of the values.
	 *
	 * @param array{sum: float, time: int, min: float|null, max: float|null, segments: list<array{float, int}>} $weighed
	 *
	 * @throws DivisionByZeroError
	 */
	private static function average(array $weighed): float|null
	{
		if ($weighed['time'] === 0) {
			return null;
		}

		$average = $weighed['sum'] / $weighed['time'];

		if (is_finite($average)) {
			return $average;
		}

		$mean = 0.0;
		$time = 0;

		foreach ($weighed['segments'] as [$value, $duration]) {
			$time += $duration;
			$share = $duration / $time;
			$mean = $mean * (1 - $share) + $value * $share;
		}

		return $mean;
	}

	/**
	 * The mean, with the same care as average() for values near the largest double
	 *
	 * @param list<float> $values
	 *
	 * @throws DivisionByZeroError
	 */
	private static function mean(array $values): float|null
	{
		if ($values === []) {
			return null;
		}

		$count = count($values);
		$mean = array_sum($values) / $count;

		if (is_finite($mean)) {
			return $mean;
		}

		$mean = 0.0;

		foreach ($values as $index => $value) {
			$share = 1 / ($index + 1);
			$mean = $mean * (1 - $share) + $value * $share;
		}

		return $mean;
	}

}
