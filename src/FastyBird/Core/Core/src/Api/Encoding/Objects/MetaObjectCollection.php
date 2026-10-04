<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

use ArrayIterator;
use Countable;
use FastyBird\Core\Exceptions;
use IteratorAggregate;
use Override;
use Traversable;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_numeric;
use function is_string;
use function sprintf;

/**
 * Meta object collection
 */
final class MetaObjectCollection implements IteratorAggregate, Countable
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<string, MetaObject>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $meta
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $meta = [])
	{
		$this->addMany($meta);
	}

	/**
	 * @phpstan-return MetaObjectCollection<string, MetaObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(StandardObject|null $metaObject): MetaObjectCollection
	{
		if ($metaObject === null) {
			return new self([]);
		}

		$data = [];

		foreach ($metaObject->keys() as $key) {
			$meta = $metaObject->get($key);

			if (is_string($meta) || is_numeric($meta) || is_array($meta)) {
				$data[$key] = new MetaObject($meta);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $meta): void
	{
		foreach ($meta as $key => $item) {
			if (!$item instanceof MetaObject || !is_string($key)) {
				throw new Exceptions\InvalidArgument('Expecting only meta objects with keys.');
			}

			$this->add($item, $key);
		}
	}

	public function add(MetaObject $meta, string $key): void
	{
		if (!$this->has($key)) {
			$this->stack[$key] = $meta;
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function get(string $key): MetaObject
	{
		if (!$this->has($key)) {
			throw new Exceptions\Runtime(sprintf('Meta member "%s" is not present.', $key));
		}

		return $this->stack[$key];
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->stack);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<string, MetaObject>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getAll(): Traversable
	{
		foreach (array_keys($this->stack) as $key) {
			yield $key => $this->get($key);
		}
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
