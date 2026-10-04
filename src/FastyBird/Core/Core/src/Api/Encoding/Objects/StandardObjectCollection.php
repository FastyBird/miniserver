<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use ArrayIterator;
use Countable;
use FastyBird\Core\Exceptions;
use IteratorAggregate;
use Override;
use SplObjectStorage;
use function array_map;
use function iterator_to_array;

/**
 * Standard objects collection
 *
 * @phpstan-implements IteratorAggregate<int, StandardObject<string, mixed>>
 */
final class StandardObjectCollection implements IteratorAggregate, Countable
{

	/** @phpstan-var SplObjectStorage<StandardObject, null> */
	private SplObjectStorage $stack;

	/**
	 * @param array<mixed> $objects
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $objects = [])
	{
		$this->stack = new SplObjectStorage();

		$this->addMany($objects);
	}

	/**
	 * @param array<mixed> $objects
	 *
	 * @phpstan-return StandardObjectCollection<int, StandardObject<string, mixed>>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $objects): self
	{
		$objects = array_map(
			static fn ($object): StandardObject => $object instanceof StandardObject ? $object : new StandardObject(
				$object,
			),
			$objects,
		);

		return new self($objects);
	}

	/**
	 * @param array<mixed> $objects
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $objects): void
	{
		foreach ($objects as $object) {
			if (!$object instanceof StandardObject) {
				throw new Exceptions\InvalidArgument('Expecting only standard objects.');
			}

			$this->add($object);
		}
	}

	/**
	 * @phpstan-param StandardObject<string, mixed> $object
	 */
	public function add(StandardObject $object): void
	{
		if (!$this->has($object)) {
			$this->stack->offsetSet($object);
		}
	}

	/**
	 * @phpstan-param StandardObject<string, mixed> $object
	 */
	public function has(StandardObject $object): bool
	{
		return $this->stack->offsetExists($object);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, StandardObject<string, mixed>>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->getAll());
	}

	/**
	 * @return array<StandardObject>
	 *
	 * @phpstan-return Array<int, StandardObject<string, mixed>>
	 */
	public function getAll(): array
	{
		return iterator_to_array($this->stack);
	}

	public function isEmpty(): bool
	{
		return $this->stack->count() === 0;
	}

	#[Override]
	public function count(): int
	{
		return $this->stack->count();
	}

}
