<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Meta object collection interface
 *
 * @phpstan-extends IteratorAggregate<string, IMetaObject>
 */
interface IMetaObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $meta
	 */
	public function addMany(array $meta): void;

	public function add(IMetaObject $meta, string $key): void;

	public function get(string $key): IMetaObject;

	public function has(string $key): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<string, IMetaObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
