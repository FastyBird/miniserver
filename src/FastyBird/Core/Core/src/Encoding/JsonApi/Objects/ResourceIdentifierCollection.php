<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Resource identifier object
 */
class ResourceIdentifierCollection implements IResourceIdentifierCollection
{

	/** @var array<IResourceIdentifierObject> */
	private array $stack;

	/**
	 * @param array<mixed> $identifiers
	 */
	public function __construct(array $identifiers = [])
	{
		$this->stack = [];

		$this->addMany($identifiers);
	}

	/**
	 * @param array<mixed> $input
	 *
	 * @phpstan-return IResourceIdentifierCollection<int, IResourceIdentifierObject>
	 */
	public static function create(array $input): IResourceIdentifierCollection
	{
		$collection = new self();

		foreach ($input as $value) {
			if (
				$value instanceof Objects\IStandardObject
				&& $value->has(JsonApi\IDocument::KEYWORD_TYPE)
				&& $value->has(JsonApi\IDocument::KEYWORD_ID)
				&& is_string($value->get(JsonApi\IDocument::KEYWORD_TYPE))
				&& is_string($value->get(JsonApi\IDocument::KEYWORD_ID))
			) {
				$collection->add(new ResourceIdentifierObject($value));
			}
		}

		return $collection;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addMany(array $identifiers): void
	{
		foreach ($identifiers as $identifier) {
			if (!$identifier instanceof IResourceIdentifierObject) {
				throw new Exceptions\InvalidArgument('Expecting only resource identifier objects.');
			}

			$this->add($identifier);
		}
	}

	public function add(IResourceIdentifierObject $identifier): void
	{
		if (!$this->has($identifier)) {
			$this->stack[] = $identifier;
		}
	}

	public function has(IResourceIdentifierObject $identifier): bool
	{
		return in_array($identifier, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IResourceIdentifierObject>
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
		return $this->stack;
	}

	public function count(): int
	{
		return count($this->stack);
	}

	public function isEmpty(): bool
	{
		return $this->stack === [];
	}

	public function isOnly(string|array $typeOrTypes): bool
	{
		foreach ($this->stack as $identifier) {
			if (!$identifier->isType($typeOrTypes)) {
				return false;
			}
		}

		return true;
	}

	public function map(array|null $typeMap = null): mixed
	{
		$ret = [];

		foreach ($this->stack as $identifier) {
			$key = is_array($typeMap) ? $identifier->mapType($typeMap) : $identifier->getType();

			if (!isset($ret[$key])) {
				$ret[$key] = [];
			}

			$ret[$key][] = $identifier->getId();
		}

		return $ret;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIds(): array
	{
		$ids = [];

		foreach ($this->stack as $identifier) {
			if ($identifier->getId() !== null) {
				$ids[] = $identifier->getId();
			}
		}

		return $ids;
	}

}
