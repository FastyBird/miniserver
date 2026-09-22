<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Override;
use Traversable;
use function array_key_exists;
use function array_keys;
use function count;
use function is_string;
use function sprintf;

/**
 * Relationship object collection
 */
final class RelationshipObjectCollection implements IRelationshipObjectCollection
{

	/**
	 * @var array<mixed>

	 * @phpstan-var Array<string, IRelationshipObject>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $relationship
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $relationship = [])
	{
		$this->addMany($relationship);
	}

	/**
	 * @phpstan-return IRelationshipObjectCollection<string, IRelationshipObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(Objects\IStandardObject|null $relationshipObject): IRelationshipObjectCollection
	{
		if ($relationshipObject === null) {
			return new self([]);
		}

		$data = [];

		foreach ($relationshipObject->keys() as $key) {
			$relationship = $relationshipObject->get($key);

			if ($relationship instanceof Objects\IStandardObject) {
				$data[$key] = new RelationshipObject($relationship);
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
	public function addMany(array $relationship): void
	{
		foreach ($relationship as $key => $item) {
			if (!$item instanceof IRelationshipObject || !is_string($key)) {
				throw new Exceptions\InvalidArgument('Expecting only relationship objects with keys.');
			}

			$this->add($item, $key);
		}
	}

	#[Override]
	public function add(IRelationshipObject $relationship, string $key): void
	{
		if (!$this->has($key)) {
			$this->stack[$key] = $relationship;
		}
	}

	#[Override]
	public function has(string $key): bool
	{
		return array_key_exists($key, $this->stack);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function get(string $key): IRelationshipObject
	{
		if (!$this->has($key)) {
			throw new Exceptions\Runtime(sprintf('Relationship member "%s" is not present.', $key));
		}

		return $this->stack[$key];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<string, IRelationshipObject>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getAll(): Traversable
	{
		foreach (array_keys($this->stack) as $key) {
			yield $key => $this->get($key);
		}
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
