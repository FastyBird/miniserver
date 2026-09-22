<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Exceptions;
use Override;
use SplObjectStorage;
use function array_map;
use function iterator_to_array;

/**
 * Standard objects collection
 */
final class StandardObjectCollection implements IStandardObjectCollection
{

	/** @phpstan-var SplObjectStorage<IStandardObject, null> */
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
	 * @phpstan-return IStandardObjectCollection<int, IStandardObject<string, mixed>>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $objects): IStandardObjectCollection
	{
		$objects = array_map(
			static fn ($object): IStandardObject => $object instanceof IStandardObject ? $object : new StandardObject(
				$object,
			),
			$objects,
		);

		return new self($objects);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function addMany(array $objects): void
	{
		foreach ($objects as $object) {
			if (!$object instanceof IStandardObject) {
				throw new Exceptions\InvalidArgument('Expecting only standard objects.');
			}

			$this->add($object);
		}
	}

	#[Override]
	public function add(IStandardObject $object): void
	{
		if (!$this->has($object)) {
			$this->stack->offsetSet($object);
		}
	}

	#[Override]
	public function has(IStandardObject $object): bool
	{
		return $this->stack->offsetExists($object);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IStandardObject<string, mixed>>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->getAll());
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getAll(): array
	{
		return iterator_to_array($this->stack);
	}

	#[Override]
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
