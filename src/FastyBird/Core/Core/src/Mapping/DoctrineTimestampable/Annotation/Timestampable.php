<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\DoctrineTimestampable\Annotation;

use Attribute;

/**
 * Property attribute marking a Doctrine entity field to be stamped with the current time on create, update or delete
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Timestampable
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
