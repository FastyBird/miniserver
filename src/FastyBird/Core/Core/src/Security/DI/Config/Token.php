<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > token structure of Security\DI\Config
 */
final readonly class Token
{

	public function __construct(
		public readonly string|null $issuer,
		public readonly string $signature,
	)
	{
	}

}
