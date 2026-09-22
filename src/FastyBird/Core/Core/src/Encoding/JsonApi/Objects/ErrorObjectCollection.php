<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use ArrayIterator;
use FastyBird\Core\Encoding\JsonApi\Objects;
use FastyBird\Core\Exceptions;
use Override;
use Traversable;
use function count;
use function in_array;

/**
 * Error object collection
 */
final class ErrorObjectCollection implements IErrorObjectCollection
{

	/** @var Array<int, IErrorObject> */
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
	 * @phpstan-return IErrorObjectCollection<int, IErrorObject>
	 *
	 * @throws Exceptions\InvalidArgument
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
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function addMany(array $error): void
	{
		foreach ($error as $item) {
			if (!$item instanceof IErrorObject) {
				throw new Exceptions\InvalidArgument('Expecting only error objects with keys.');
			}

			$this->add($item);
		}
	}

	#[Override]
	public function add(IErrorObject $error): void
	{
		if (!$this->has($error)) {
			$this->stack[] = $error;
		}
	}

	#[Override]
	public function has(IErrorObject $error): bool
	{
		return in_array($error, $this->stack, true);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return ArrayIterator<int, IErrorObject>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->stack);
	}

	#[Override]
	public function getAll(): Traversable
	{
		return $this->getIterator();
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
