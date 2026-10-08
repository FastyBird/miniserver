<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI;

/**
 * The fbCore > security section, which SecurityExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\Token $token,
		public readonly Config\Enable $enable,
		public readonly Config\Application $application,
		public readonly Config\Services $services,
		public readonly Config\Casbin $casbin,
	)
	{
	}

}
