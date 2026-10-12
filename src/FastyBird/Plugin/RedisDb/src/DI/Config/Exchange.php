<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\DI\Config;

/**
 * The fbRedisDbPlugin > exchange structure of RedisDb\DI\Config
 */
final readonly class Exchange
{

	public function __construct(public readonly string $channel)
	{
	}

}
