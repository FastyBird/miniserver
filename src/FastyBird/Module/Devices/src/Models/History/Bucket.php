<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

/**
 * A stretch of a property's history, from its start time on: the minimum, maximum, time-weighted average and
 * last value of what the property held in it, and how many samples fell in it
 *
 * The four values are null when the property was in a gap, a state that was not valid, for the whole stretch.
 */
final readonly class Bucket
{

	public function __construct(
		public int $time,
		public float|null $min,
		public float|null $max,
		public float|null $avg,
		public float|null $last,
		public int $samples,
	)
	{
	}

}
