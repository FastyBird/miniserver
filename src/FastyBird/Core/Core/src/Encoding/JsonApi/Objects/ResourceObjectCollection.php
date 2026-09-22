<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Override;
use Traversable;
use function count;
use function in_array;

/**
 * Resource object collection
 */
final class ResourceObjectCollection implements IResourceObjectCollection
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<int, IResourceObject>
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
	 * @phpstan-return IResourceObjectCollection<int, IResourceObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $resourceArray): IResourceObjectCollection
	{
		$data = [];

		foreach ($resourceArray as $resource) {
			if ($resource instanceof Objects\IStandardObject) {
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
	#[Override]
	public function addMany(array $resource): void
	{
		foreach ($resource as $item) {
			if (!$item instanceof IResourceObject) {
				throw new Exceptions\InvalidArgument('Expecting only resource objects with keys.');
			}

			$this->add($item);
		}
	}

	#[Override]
	public function add(IResourceObject $resource): void
	{
		if (!$this->has($resource)) {
			$this->stack[] = $resource;
		}
	}

	#[Override]
	public function has(IResourceObject $resource): bool
	{
		return in_array($resource, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IResourceObject>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	#[Override]
	public function getAll(): Traversable
	{
		return $this->getIterator();
	}

	#[Override]
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
