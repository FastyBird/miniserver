<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\DI\Config;

/**
 * The fbRedisDbPlugin > client structure of RedisDb\DI\Config
 */
final readonly class Client
{

	public function __construct(
		public readonly string $host,
		public readonly int $port,
		public readonly string|null $username,
		public readonly string|null $password,
	)
	{
	}

}
