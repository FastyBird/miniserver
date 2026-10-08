<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > services structure of Security\DI\Config
 */
final readonly class Services
{

	public function __construct(public readonly bool $identity)
	{
	}

}
