<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DI;

/**
 * The fbCore > persistence section, which PersistenceExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\Timestampable $timestampable,
	)
	{
	}

}
