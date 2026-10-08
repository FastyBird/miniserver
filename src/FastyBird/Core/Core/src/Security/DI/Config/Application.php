<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI\Config;

/**
 * The fbCore > security > application structure of Security\DI\Config
 */
final readonly class Application
{

	public function __construct(
		public readonly string|null $signInUrl,
		public readonly string $homeUrl,
	)
	{
	}

}
