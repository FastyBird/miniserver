<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\DI;

/**
 * The fbCore > documents section, which DocumentsExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	/**
	 * @param array<string, string> $mapping
	 * @param array<string, string> $excludePaths
	 */
	public function __construct(
		public readonly array $mapping,
		public readonly array $excludePaths,
	)
	{
	}

}
