<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Relationship object collection interface
 *
 * @phpstan-extends IteratorAggregate<string, IRelationshipObject>
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
