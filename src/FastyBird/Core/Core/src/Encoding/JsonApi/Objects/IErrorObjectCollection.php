<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Error object collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IErrorObject>
 */
interface IErrorObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $error
	 */
	public function addMany(array $error): void;

	public function add(IErrorObject $error): void;

	public function has(IErrorObject $error): bool;

	/**
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<int, IErrorObject>
	 */
	public function getAll(): Traversable;

	public function isEmpty(): bool;

	public function count(): int;

}
