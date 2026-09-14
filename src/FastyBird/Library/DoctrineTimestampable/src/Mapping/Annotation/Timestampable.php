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

use Doctrine\Common\Annotations\Annotation;

/**
 * Doctrine Timestampable annotation for Doctrine2
 *
 * @package        iPublikuj:DoctrineTimestampable!
 * @subpackage     Annotation
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class Timestampable extends Annotation
{

	public string $on = 'update';

	/** @var string|array<string> */
	public string|array $field;

	// Deliberately untyped. Doctrine\Common\Annotations\Annotation declares this property
	// without a type, and PHP forbids narrowing an inherited untyped property to one -- adding
	// `mixed` here is a fatal "Type of ...::$value must not be defined" at class load. The
	// coding standard's property type hint sniff will add it back if given the chance.
	// phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
	public $value;

}
