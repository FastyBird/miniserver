<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI\Config;

/**
 * The fbCore > webSockets > storage > topics structure of WebSockets\DI\Config
 */
final readonly class StorageTopics
{

	public function __construct(
		public readonly string $driver,
		public readonly int $ttl,
	)
	{
	}

}
