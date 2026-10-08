<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DI\Config;

/**
 * The fbCore > persistence > timestampable structure of Persistence\DI\Config
 */
final readonly class Timestampable
{

	public function __construct(
		public readonly bool $lazyAssociation,
		public readonly bool $autoMapField,
		public readonly string $dbFieldType,
	)
	{
	}

}
