<?php declare(strict_types = 1);

/**
 * StandardObjectCollection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           18.05.21
 */

namespace FastyBird\Library\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Library\JsonApi\Exceptions;
use SplObjectStorage;
use function array_map;
use function iterator_to_array;

/**
 * Standard objects collection
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class StandardObjectCollection implements IStandardObjectCollection
{

	/** @phpstan-var SplObjectStorage<IStandardObject, null> */
	private SplObjectStorage $stack;

	/**
	 * @param array<mixed> $objects
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
	 */
	public function addMany(array $objects): void
	{
		foreach ($objects as $object) {
			if (!$object instanceof IStandardObject) {
				throw new Exceptions\InvalidArgument('Expecting only standard objects.');
			}

			$this->add($object);
		}
	}

	public function add(IStandardObject $object): void
	{
		if (!$this->has($object)) {
			$this->stack->offsetSet($object);
		}
	}

	public function has(IStandardObject $object): bool
	{
		return $this->stack->offsetExists($object);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IStandardObject<string, mixed>>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->getAll());
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAll(): array
	{
		return iterator_to_array($this->stack);
	}

	public function isEmpty(): bool
	{
		return $this->stack->count() === 0;
	}

	public function count(): int
	{
		return $this->stack->count();
	}

}
