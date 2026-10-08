<?php declare(strict_types = 1);

namespace FastyBird\Core\Clock\DI;

/**
 * The fbCore > clock section, which ClockExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	public function __construct(
		public readonly string $timeZone,
		public readonly bool $system,
		public readonly mixed $frozen,
	)
	{
	}

}
