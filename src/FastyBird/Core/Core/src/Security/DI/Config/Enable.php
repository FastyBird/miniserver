<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > enable structure of Security\DI\Config
 */
final readonly class Enable
{

	public function __construct(
		public readonly bool $middleware,
		public readonly EnableDoctrine $doctrine,
		public readonly EnableCasbin $casbin,
		public readonly EnableNette $nette,
	)
	{
	}

}
