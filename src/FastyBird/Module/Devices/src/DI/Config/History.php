<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\DI\Config;

/**
 * The fbDevicesModule > history structure of Devices\DI\Config
 */
final readonly class History
{

	public function __construct(
		public readonly bool $enabled,
		public readonly int $flushInterval,
		public readonly int $minInterval,
		public readonly HistoryRetention $retention,
	)
	{
	}

}
