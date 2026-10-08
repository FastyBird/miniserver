<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging\DI\Config;

use Monolog;

/**
 * The fbCore > logging > console structure of Logging\DI\Config
 */
final readonly class Console
{

	public function __construct(
		public readonly bool $enabled,
		public readonly int|Monolog\Level $level,
	)
	{
	}

}
