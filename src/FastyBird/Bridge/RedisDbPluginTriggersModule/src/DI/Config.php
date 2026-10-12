<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\DI;

/**
 * The fbRedisDbPluginTriggersModuleBridge configuration, which
 * RedisDbPluginTriggersModuleExtension::getConfigSchema() declares and casts to this class
 */
final readonly class Config
{

	public function __construct(public readonly int $database)
	{
	}

}
