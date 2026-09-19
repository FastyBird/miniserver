<?php declare(strict_types = 1);

/**
 * IResourceIdentifierObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.0.1
 *
 * @date           05.05.18
 */

namespace FastyBird\Library\JsonApi\Objects;

use FastyBird\Library\JsonApi\Exceptions;

/**
 * Resource identifier interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IResourceIdentifierObject
{

	public function getId(): string|null;

	public function getType(): string;

	/**
	 * @param string|array<string> $typeOrTypes
	 */
	public function isType(string|array $typeOrTypes): bool;

	/**
	 * From the supplied array, return the value where the current type is the key
	 *
	 * @param array<string> $types
	 *
	 * @throws Exceptions\Runtime if the current type is not one of those in the supplied $types
	 */
	public function mapType(array $types): string;

	public function isSame(self $identifier): bool;

	public function toString(): string;

}
