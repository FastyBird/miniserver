<?php declare(strict_types = 1);

/**
 * IRelationshipObjectCollection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Relationship object collection interface
 *
 * @phpstan-extends IteratorAggregate<string, IRelationshipObject>
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IRelationshipObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $relationship
	 */
	public function addMany(array $relationship): void;

	public function add(IRelationshipObject $relationship, string $key): void;

	public function has(string $key): bool;

	public function get(string $key): IRelationshipObject;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<string, IRelationshipObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
