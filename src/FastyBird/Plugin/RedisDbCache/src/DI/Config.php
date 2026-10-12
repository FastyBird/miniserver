<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\DI;

/**
 * The fbRedisDbCachePlugin configuration, which RedisDbCacheExtension::getConfigSchema() declares
 * and casts to this class
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\Client $client,
		public readonly bool $autowired,
	)
	{
	}

}
