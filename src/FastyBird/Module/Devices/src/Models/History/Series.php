<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

use DateTimeImmutable;
use FastyBird\Module\Devices\Types;

/**
 * A property's history over a period, as columns
 *
 * The columns are as long as `time`, and the same index is one point: its start in UTC epoch seconds, and what
 * the property held from then on for `resolution` seconds. A column holds null for a point where the property
 * was in a gap. The shape is the one a chart library takes. A point exists only from the first moment the
 * history knows the property's value.
 */
final readonly class Series
{

	/**
	 * @param list<int> $time
	 * @param list<float|null> $min
	 * @param list<float|null> $max
	 * @param list<float|null> $avg
	 * @param list<float|null> $last
	 */
	public function __construct(
		public DateTimeImmutable $from,
		public DateTimeImmutable $to,
		public int $resolution,
		public Types\HistoryTier $tier,
		public array $time,
		public array $min,
		public array $max,
		public array $avg,
		public array $last,
	)
	{
	}

}
