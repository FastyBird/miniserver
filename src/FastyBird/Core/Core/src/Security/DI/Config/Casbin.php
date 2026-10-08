<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > casbin structure of Security\DI\Config
 */
final readonly class Casbin
{

	public function __construct(
		public readonly string $model,
		public readonly string|null $policy,
	)
	{
	}

}
