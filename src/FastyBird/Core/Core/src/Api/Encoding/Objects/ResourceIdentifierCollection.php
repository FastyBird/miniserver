<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use ArrayIterator;
use Countable;
use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use IteratorAggregate;
use Override;
use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Resource identifier object
 */
final class ResourceIdentifierCollection implements IteratorAggregate, Countable
{

	/** @var array<ResourceIdentifierObject> */
	private array $stack;

	/**
	 * @param array<mixed> $identifiers
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $identifiers = [])
	{
		$this->stack = [];

		$this->addMany($identifiers);
	}

	/**
	 * @param array<mixed> $input
	 *
	 * @phpstan-return ResourceIdentifierCollection<int, ResourceIdentifierObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $input): ResourceIdentifierCollection
	{
		$collection = new self();

		foreach ($input as $value) {
			if (
				$value instanceof StandardObject
				&& $value->has(Encoding\Document::KEYWORD_TYPE)
				&& $value->has(Encoding\Document::KEYWORD_ID)
				&& is_string($value->get(Encoding\Document::KEYWORD_TYPE))
				&& is_string($value->get(Encoding\Document::KEYWORD_ID))
			) {
				$collection->add(new ResourceIdentifierObject($value));
			}
		}

		return $collection;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $identifiers): void
	{
		foreach ($identifiers as $identifier) {
			if (!$identifier instanceof ResourceIdentifierObject) {
				throw new Exceptions\InvalidArgument('Expecting only resource identifier objects.');
			}

			$this->add($identifier);
		}
	}

	public function add(ResourceIdentifierObject $identifier): void
	{
		if (!$this->has($identifier)) {
			$this->stack[] = $identifier;
		}
	}

	public function has(ResourceIdentifierObject $identifier): bool
	{
		return in_array($identifier, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, ResourceIdentifierObject>
	 */
	#[Override]
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

	#[Override]
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

	/**
	 * @throws Exceptions\Runtime
	 */
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
