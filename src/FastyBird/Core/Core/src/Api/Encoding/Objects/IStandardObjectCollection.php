<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use Countable;
use IteratorAggregate;

/**
 * Standard objects collection interface
 *
 * @phpstan-extends IteratorAggregate<int, IStandardObject<string, mixed>>
 */
interface IStandardObjectCollection extends IteratorAggregate, Countable
{

	/**
	 * @param array<mixed> $objects
	 */
	public function addMany(array $objects): void;

	/**
	 * @phpstan-param IStandardObject<string, mixed> $object
	 */
	public function add(IStandardObject $object): void;

	/**
	 * @phpstan-param IStandardObject<string, mixed> $object
	 */
	public function has(IStandardObject $object): bool;

	/**
	 * @return array<IStandardObject>
	 *
	 * @phpstan-return Array<int, IStandardObject<string, mixed>>
	 */
	public function getAll(): array;

	public function isEmpty(): bool;

}
