<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use FastyBird\Core\Exceptions;

/**
 * Resource identifier interface
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
