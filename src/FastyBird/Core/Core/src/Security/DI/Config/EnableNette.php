<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > enable > nette structure of Security\DI\Config
 */
final readonly class EnableNette
{

	public function __construct(public readonly bool $application)
	{
	}

}
