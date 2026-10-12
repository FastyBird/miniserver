<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\DI;

/**
 * The fbDevicesModuleUiModuleBridge configuration, which
 * DevicesModuleUiModuleExtension::getConfigSchema() declares and casts to this class
 */
final readonly class Config
{

	public function __construct(public readonly int $database)
	{
	}

}
