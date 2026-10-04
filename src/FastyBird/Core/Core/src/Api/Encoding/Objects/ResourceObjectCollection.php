<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use ArrayIterator;
use Countable;
use FastyBird\Core\Exceptions;
use IteratorAggregate;
use Override;
use Traversable;
use function count;
use function in_array;

/**
 * Resource object collection
 */
final class ResourceObjectCollection implements IteratorAggregate, Countable
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<int, ResourceObject>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $resource
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $resource = [])
	{
		$this->addMany($resource);
	}

	/**
	 * @param array<mixed> $resourceArray
	 *
	 * @phpstan-return ResourceObjectCollection<int, ResourceObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $resourceArray): ResourceObjectCollection
	{
		$data = [];

		foreach ($resourceArray as $resource) {
			if ($resource instanceof StandardObject) {
				$data[] = new ResourceObject($resource);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $resource): void
	{
		foreach ($resource as $item) {
			if (!$item instanceof ResourceObject) {
				throw new Exceptions\InvalidArgument('Expecting only resource objects with keys.');
			}

			$this->add($item);
		}
	}

	public function add(ResourceObject $resource): void
	{
		if (!$this->has($resource)) {
			$this->stack[] = $resource;
		}
	}

	public function has(ResourceObject $resource): bool
	{
		return in_array($resource, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, ResourceObject>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	public function getAll(): Traversable
	{
		return $this->getIterator();
	}

	public function isEmpty(): bool
	{
		return $this->stack === [];
	}

	#[Override]
	public function count(): int
	{
		return count($this->stack);
	}

}
