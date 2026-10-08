<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI;

/**
 * The fbCore > http section, which HttpExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\StaticFiles $static,
		public readonly Config\Server $server,
		public readonly Config\Cors $cors,
	)
	{
	}

}
