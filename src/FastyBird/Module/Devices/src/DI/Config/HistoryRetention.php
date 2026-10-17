<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\DI\Config;

/**
 * The fbDevicesModule > history > retention structure of Devices\DI\Config, in days
 */
final readonly class HistoryRetention
{

	public function __construct(
		public readonly int $samples,
		public readonly int $fiveMinutes,
		public readonly int $hours,
	)
	{
	}

}
