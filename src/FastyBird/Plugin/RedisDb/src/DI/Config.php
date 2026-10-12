<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\DI;

/**
 * The fbRedisDbPlugin configuration, which RedisDbExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\Client $client,
		public readonly Config\Exchange $exchange,
	)
	{
	}

}
