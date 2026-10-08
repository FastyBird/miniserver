<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI\Config;

/**
 * The fbCore > webSockets > access structure of WebSockets\DI\Config
 */
final readonly class Access
{

	public function __construct(
		public readonly string|null $keys,
		public readonly string|null $origins,
	)
	{
	}

}
