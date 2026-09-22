<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Traversable;
use function array_key_exists;
use function array_keys;
use function count;
use function is_string;
use function sprintf;

/**
 * Link object collection
 */
class LinkObjectCollection implements ILinkObjectCollection
{

	/**
	 * @var array<mixed>
	 *
	 * @phpstan-var Array<string, ILinkObject|string>
	 */
	private array $stack = [];

	/**
	 * @param array<mixed> $link
	 */
	public function __construct(array $link = [])
	{
		$this->addMany($link);
	}

	/**
	 * @phpstan-return ILinkObjectCollection<string, ILinkObject|string>
	 */
	public static function create(Objects\IStandardObject|null $linkObject): ILinkObjectCollection
	{
		if ($linkObject === null) {
			return new self([]);
		}

		$data = [];

		foreach ($linkObject->keys() as $key) {
			$link = $linkObject->get($key);

			if (is_string($link)) {
				$data[$key] = $link;

			} elseif ($link instanceof Objects\IStandardObject) {
				$data[$key] = new LinkObject($link);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 */
	public function addMany(array $link): void
	{
		foreach ($link as $key => $item) {
			if ((!$item instanceof ILinkObject && !is_string($item)) || !is_string($key)) {
				throw new Exceptions\InvalidArgument('Expecting only link objects with keys.');
			}

			$this->add($item, $key);
		}
	}

	public function add(ILinkObject|string $link, string $key): void
	{
		if (!$this->has($key)) {
			$this->stack[$key] = $link;
		}
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->stack);
	}

	public function get(string $key): string|ILinkObject
	{
		if (!$this->has($key)) {
			throw new Exceptions\Runtime(sprintf('Link member "%s" is not present.', $key));
		}

		return $this->stack[$key];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<string, ILinkObject|string>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return Traversable<string, ILinkObject|string>
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

	public function count(): int
	{
		return count($this->stack);
	}

}
