<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\DI;

/**
 * The fbRedisDbPluginDevicesModuleBridge configuration, which
 * RedisDbPluginDevicesModuleExtension::getConfigSchema() declares and casts to this class
 */
final readonly class Config
{

	public function __construct(public readonly int $database)
	{
	}

}
