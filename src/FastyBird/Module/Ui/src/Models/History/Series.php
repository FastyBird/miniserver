<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Models\History;

use DateTimeImmutable;
use FastyBird\Module\Ui\Types;

/**
 * The history of the property a data source shows, over a period, as columns
 *
 * The columns are as long as `time`, and the same index is one point: its start in UTC epoch seconds, and what
 * the property held from then on for `resolution` seconds. A column holds null for a point where the property
 * had no valid value. The shape is the one a chart library takes. `id` is the id of the data source, which is
 * the id of the JSON:API resource.
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
		public string $id,
		public DateTimeImmutable $from,
		public DateTimeImmutable $to,
		public int $resolution,
		public Types\HistoryTier $tier,
		public string|null $unit,
		public string $dataType,
		public array $time,
		public array $min,
		public array $max,
		public array $avg,
		public array $last,
	)
	{
	}

}
