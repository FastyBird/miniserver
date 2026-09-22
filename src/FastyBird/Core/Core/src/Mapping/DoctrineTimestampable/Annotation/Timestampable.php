<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\DoctrineTimestampable\Annotation;

use Attribute;

/**
 * Doctrine Timestampable annotation for Doctrine2
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Timestampable
{

	/**
	 * @param string|array<string>|null $field
	 */
	public function __construct(
		public readonly string $on = 'update',
		public readonly string|array|null $field = null,
		public readonly mixed $value = null,
	)
	{
	}

}
