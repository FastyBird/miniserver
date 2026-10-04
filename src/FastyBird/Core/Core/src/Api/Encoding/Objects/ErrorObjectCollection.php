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
 * Error object collection
 */
final class ErrorObjectCollection implements IteratorAggregate, Countable
{

	/** @var Array<int, ErrorObject> */
	private array $stack = [];

	/**
	 * @param array<mixed> $error
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(array $error = [])
	{
		$this->addMany($error);
	}

	/**
	 * @param array<mixed> $errorArray
	 *
	 * @phpstan-return ErrorObjectCollection<int, ErrorObject>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function create(array $errorArray): self
	{
		$data = [];

		foreach ($errorArray as $error) {
			if ($error instanceof StandardObject) {
				$data[] = new ErrorObject($error);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function addMany(array $error): void
	{
		foreach ($error as $item) {
			if (!$item instanceof ErrorObject) {
				throw new Exceptions\InvalidArgument('Expecting only error objects with keys.');
			}

			$this->add($item);
		}
	}

	public function add(ErrorObject $error): void
	{
		if (!$this->has($error)) {
			$this->stack[] = $error;
		}
	}

	public function has(ErrorObject $error): bool
	{
		return in_array($error, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, ErrorObject>
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
