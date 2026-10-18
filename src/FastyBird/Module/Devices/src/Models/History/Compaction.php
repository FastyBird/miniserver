<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\History;

/**
 * What one run of the compactor did
 */
final readonly class Compaction
{

	public function __construct(
		public int $properties,
		public int $failed,
		public int $fiveMinutes,
		public int $hours,
		public int $expiredSamples,
		public int $expiredFiveMinutes,
		public int $expiredHours,
	)
	{
	}

}
