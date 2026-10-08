<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging\DI\Config;

use Monolog;

/**
 * The fbCore > logging > rotatingFile structure of Logging\DI\Config
 */
final readonly class RotatingFile
{

	public function __construct(
		public readonly bool $enabled,
		public readonly int|Monolog\Level $level,
		public readonly string $filename,
	)
	{
	}

}
