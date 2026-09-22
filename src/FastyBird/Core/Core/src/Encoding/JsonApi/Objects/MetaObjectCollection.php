<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
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
class MetaObjectCollection implements IMetaObjectCollection
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<string, IMetaObject>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $meta
	 */
	public function __construct(array $meta = [])
	{
		$this->addMany($meta);
	}

	/**
	 * @phpstan-return IMetaObjectCollection<string, IMetaObject>
	 */
	public static function create(Objects\IStandardObject|null $metaObject): IMetaObjectCollection
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
	 */
	public function addMany(array $meta): void
	{
		foreach ($meta as $key => $item) {
			if (!$item instanceof IMetaObject || !is_string($key)) {
				throw new Exceptions\InvalidArgument('Expecting only meta objects with keys.');
			}

			$this->add($item, $key);
		}
	}

	public function add(IMetaObject $meta, string $key): void
	{
		if (!$this->has($key)) {
			$this->stack[$key] = $meta;
		}
	}

	public function get(string $key): IMetaObject
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
	 * @phpstan-return ArrayIterator<string, IMetaObject>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

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

	public function count(): int
	{
		return count($this->stack);
	}

}
