<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Traversable;
use function count;
use function in_array;

/**
 * Error object collection
 */
class ErrorObjectCollection implements IErrorObjectCollection
{

	/** @var Array<int, IErrorObject> */
	private array $stack = [];

	/**
	 * @param array<mixed> $error
	 */
	public function __construct(array $error = [])
	{
		$this->addMany($error);
	}

	/**
	 * @param array<mixed> $errorArray
	 *
	 * @phpstan-return IErrorObjectCollection<int, IErrorObject>
	 */
	public static function create(array $errorArray): IErrorObjectCollection
	{
		$data = [];

		foreach ($errorArray as $error) {
			if ($error instanceof Objects\IStandardObject) {
				$data[] = new ErrorObject($error);
			}
		}

		return new self($data);
	}

	/**
	 * {@inheritDoc}
	 */
	public function addMany(array $error): void
	{
		foreach ($error as $item) {
			if (!$item instanceof IErrorObject) {
				throw new Exceptions\InvalidArgument('Expecting only error objects with keys.');
			}

			$this->add($item);
		}
	}

	public function add(IErrorObject $error): void
	{
		if (!$this->has($error)) {
			$this->stack[] = $error;
		}
	}

	public function has(IErrorObject $error): bool
	{
		return in_array($error, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IErrorObject>
	 */
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

	public function count(): int
	{
		return count($this->stack);
	}

}
