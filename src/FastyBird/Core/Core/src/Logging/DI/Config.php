<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging\DI;

/**
 * The fbCore > logging section, which LoggingExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\RotatingFile $rotatingFile,
		public readonly Config\StdOut $stdOut,
		public readonly Config\Console $console,
		public readonly Config\Sentry $sentry,
	)
	{
	}

}
