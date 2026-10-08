<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > enable > doctrine structure of Security\DI\Config
 */
final readonly class EnableDoctrine
{

	public function __construct(
		public readonly bool $mapping,
		public readonly bool $models,
	)
	{
	}

}
