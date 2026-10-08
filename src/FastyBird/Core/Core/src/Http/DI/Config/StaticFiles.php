<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI\Config;

/**
 * The fbCore > http > static structure of Http\DI\Config
 */
final readonly class StaticFiles
{

	public function __construct(
		public readonly string|null $publicRoot,
		public readonly bool $enabled,
	)
	{
	}

}
