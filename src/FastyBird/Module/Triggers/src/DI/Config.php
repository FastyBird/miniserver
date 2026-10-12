<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\DI;

/**
 * The fbTriggersModule configuration, which TriggersExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
