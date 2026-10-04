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
 *
 * @phpstan-implements IteratorAggregate<int, ResourceIdentifierObject>
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
	public static function create(array $input): self
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
	 * @param array<mixed> $identifiers
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

	/**
	 * Does the collection contain the supplied identifier?
	 */
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
	 * Get the collection as an array
	 *
	 * @return array<ResourceIdentifierObject>
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

	/**
	 * Is the collection empty?
	 */
	public function isEmpty(): bool
	{
		return $this->stack === [];
	}

	/**
	 * Does every identifier in the collection match the supplied type/any of the supplied types?
	 *
	 * @param string|array<string> $typeOrTypes
	 */
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
	 * Map the collection to an array of type keys and id values
	 *
	 * For example, this JSON structure:
	 *
	 * ```
	 * [
	 *  {"type": "foo", "id": "1"},
	 *  {"type": "foo", "id": "2"},
	 *  {"type": "bar", "id": "99"}
	 * ]
	 * ```
	 *
	 * Will map to:
	 *
	 * ```
	 * [
	 *  "foo" => ["1", "2"],
	 *  "bar" => ["99"]
	 * ]
	 * ```
	 *
	 * If the method call is provided with the an array `['foo' => 'FooModel', 'bar' => 'FoobarModel']`, then the
	 * returned mapped array will be:
	 *
	 * ```
	 * [
	 *  "FooModel" => ["1", "2"],
	 *  "FoobarModel" => ["99"]
	 * ]
	 * ```
	 *
	 * @param array<string>|null $typeMap if an array, map the identifier types to the supplied types.
	 *
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
	 * Get an array of the ids of each identifier in the collection
	 *
	 * @return array<string>
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
