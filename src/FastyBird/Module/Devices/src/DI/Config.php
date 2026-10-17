<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\DI;

/**
 * The fbDevicesModule configuration, which DevicesExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(
		public readonly bool $apiPrefix,
		public readonly bool $exchange,
		public readonly Config\History $history,
	)
	{
	}

}
