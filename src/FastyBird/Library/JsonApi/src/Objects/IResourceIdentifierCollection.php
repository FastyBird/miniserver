<?php declare(strict_types = 1);

/**
 * IResourceIdentifierCollection.php
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

use Countable;
use IteratorAggregate;

/**
 * Resource identifiers collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IResourceIdentifierObject>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IResourceIdentifierCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $identifiers
	 */
	public function addMany(array $identifiers): void;

	public function add(IResourceIdentifierObject $identifier): void;

	/**
	 * Does the collection contain the supplied identifier?
	 */
	public function has(IResourceIdentifierObject $identifier): bool;

	/**
	 * Get the collection as an array
	 *
	 * @return array<IResourceIdentifierObject>
	 */
	public function getAll(): array;

	/**
	 * Is the collection empty?
	 */
	public function isEmpty(): bool;

	/**
	 * Does every identifier in the collection match the supplied type/any of the supplied types?
	 *
	 * @param string|array<string> $typeOrTypes
	 */
	public function isOnly(string|array $typeOrTypes): bool;

	/**
	 * Map the collection to an array of type keys and id values
	 *
	 * For example, this JSON structure:
	 *
	 * ```
	 * [
	 *  {"type": "foo", "id": "1"},
	 *  {"type": "foo", "id": "2"},
	 *  {"type": "bar", "id": "99"}
	 * ]
	 * ```
	 *
	 * Will map to:
	 *
	 * ```
	 * [
	 *  "foo" => ["1", "2"],
	 *  "bar" => ["99"]
	 * ]
	 * ```
	 *
	 * If the method call is provided with the an array `['foo' => 'FooModel', 'bar' => 'FoobarModel']`, then the
	 * returned mapped array will be:
	 *
	 * ```
	 * [
	 *  "FooModel" => ["1", "2"],
	 *  "FoobarModel" => ["99"]
	 * ]
	 * ```
	 *
	 * @param array<string>|null $typeMap if an array, map the identifier types to the supplied types.
	 */
	public function map(array|null $typeMap = null): mixed;

	/**
	 * Get an array of the ids of each identifier in the collection
	 *
	 * @return array<string>
	 */
	public function getIds(): array;

}
