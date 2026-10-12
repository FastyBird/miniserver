<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\DI;

/**
 * The fbAccountsModule configuration, which AccountsExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
