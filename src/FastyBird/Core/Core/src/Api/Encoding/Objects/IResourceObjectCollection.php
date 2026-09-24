<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Resource object collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IResourceObject>
 */
interface IResourceObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $resource
	 */
	public function addMany(array $resource): void;

	public function add(IResourceObject $resource): void;

	public function has(IResourceObject $resource): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<int, IResourceObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
