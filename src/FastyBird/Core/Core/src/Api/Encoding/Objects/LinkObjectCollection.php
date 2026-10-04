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
use function is_string;
use function sprintf;

/**
 * Link object collection
 */
final class LinkObjectCollection implements IteratorAggregate, Countable
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<string, LinkObject|string>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $link
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $link = [])
	{
		$this->addMany($link);
	}

	/**
	 * @phpstan-return LinkObjectCollection<string, LinkObject|string>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(StandardObject|null $linkObject): LinkObjectCollection
	{
		if ($linkObject === null) {
			return new self([]);
		}

		$data = [];

		foreach ($linkObject->keys() as $key) {
			$link = $linkObject->get($key);

			if (is_string($link)) {
				$data[$key] = $link;

			} elseif ($link instanceof StandardObject) {
				$data[$key] = new LinkObject($link);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $link): void
	{
		foreach ($link as $key => $item) {
			if ((!$item instanceof LinkObject && !is_string($item)) || !is_string($key)) {
				throw new Exceptions\InvalidArgument('Expecting only link objects with keys.');
			}

			$this->add($item, $key);
		}
	}

	public function add(LinkObject|string $link, string $key): void
	{
		if (!$this->has($key)) {
			$this->stack[$key] = $link;
		}
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->stack);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function get(string $key): string|LinkObject
	{
		if (!$this->has($key)) {
			throw new Exceptions\Runtime(sprintf('Link member "%s" is not present.', $key));
		}

		return $this->stack[$key];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<string, LinkObject|string>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return Traversable<string, LinkObject|string>
	 *
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
