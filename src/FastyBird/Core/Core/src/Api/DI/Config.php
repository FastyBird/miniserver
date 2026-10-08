<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\DI;

/**
 * The fbCore > api section, which ApiExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(public readonly Config\Meta $meta)
	{
	}

}
