<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI\Config;

/**
 * The fbCore > http > cors > allow structure of Http\DI\Config
 */
final readonly class CorsAllow
{

	/**
	 * @param array<string> $methods
	 * @param array<string> $headers
	 */
	public function __construct(
		public readonly string $origin,
		public readonly array $methods,
		public readonly bool $credentials,
		public readonly array $headers,
	)
	{
	}

}
