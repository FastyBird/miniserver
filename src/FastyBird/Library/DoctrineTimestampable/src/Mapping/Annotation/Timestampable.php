<?php declare(strict_types = 1);

/**
 * Timestampable.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Annotation
 * @since          1.0.0
 *
 * @date           06.01.16
 */

namespace FastyBird\Library\DoctrineTimestampable\Mapping\Annotation;

use Attribute;

/**
 * Doctrine Timestampable annotation for Doctrine2
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Annotation
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
