<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI\Config;

/**
 * The fbCore > http > cors structure of Http\DI\Config
 */
final readonly class Cors
{

	public function __construct(
		public readonly bool $enabled,
		public readonly CorsAllow $allow,
	)
	{
	}

}
