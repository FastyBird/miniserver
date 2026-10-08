<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging\DI\Config;

use Monolog;

/**
 * The fbCore > logging > sentry structure of Logging\DI\Config
 */
final readonly class Sentry
{

	public function __construct(
		public readonly string|null $dsn,
		public readonly int|Monolog\Level $level,
	)
	{
	}

}
