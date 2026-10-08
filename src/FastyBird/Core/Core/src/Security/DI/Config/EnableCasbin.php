<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > enable > casbin structure of Security\DI\Config
 */
final readonly class EnableCasbin
{

	public function __construct(public readonly bool $database)
	{
	}

}
