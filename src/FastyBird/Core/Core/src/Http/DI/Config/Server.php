<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI\Config;

/**
 * The fbCore > http > server structure of Http\DI\Config
 */
final readonly class Server
{

	public function __construct(
		public readonly string $address,
		public readonly int $port,
		public readonly string|null $certificate,
	)
	{
	}

}
