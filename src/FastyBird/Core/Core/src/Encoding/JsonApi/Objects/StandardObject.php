<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use IteratorAggregate;
use OutOfBoundsException;
use Override;
use stdClass;
use Traversable;
use function array_keys;
use function count;
use function get_object_vars;
use function is_array;
use function property_exists;
use function sprintf;

/**
 * @phpstan-implements IteratorAggregate<mixed, mixed|IStandardObject>
 */
final class StandardObject implements IteratorAggregate, IStandardObject
{

	protected stdClass $proxy;

	public function __construct(stdClass|null $proxy = null)
	{
		$this->proxy = $proxy ?? new stdClass();
	}

	#[Override]
	public function get(string $key, mixed $default = null): string|int|float|bool|array|self|null
	{
		return Obj::get($this->proxy, $key, $default);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getMany(string|array ...$keys): array
	{
		$values = [];

		foreach ($this->normalizeKeys($keys) as $key) {
			$values[$key] = $this->has($key) ? $this->proxy->{$key} : null;
		}

		return $values;
	}

	#[Override]
	public function set(string $key, mixed $value): IStandardObject
	{
		$this->proxy->{$key} = $value;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function setMany(array $values): IStandardObject
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value);
		}

		return $this;
	}

	#[Override]
	public function has(string $key): bool
	{
		foreach ($this->normalizeKeys([$key]) as $normalizedKey) {
			if (!property_exists($this->proxy, $normalizedKey)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function hasAny(array ...$keys): bool
	{
		foreach ($this->normalizeKeys($keys) as $key) {
			if ($this->has($key)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function keys(): array
	{
		return array_keys(get_object_vars($this->proxy));
	}

	#[Override]
	public function copy(): IStandardObject
	{
		return clone $this;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function remove(array ...$keys): IStandardObject
	{
		foreach ($this->normalizeKeys($keys) as $key) {
			unset($this->proxy->{$key});
		}

		return $this;
	}

	#[Override]
	public function toStdClass(): stdClass
	{
		return Obj::replicate($this->proxy);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return Obj::toArray($this->proxy);
	}

	#[Override]
	public function jsonSerialize(): stdClass
	{
		return $this->proxy;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return Traversable<string, mixed>
	 */
	#[Override]
	public function getIterator(): Traversable
	{
		return Obj::traverse($this->proxy);
	}

	#[Override]
	public function count(): int
	{
		return count($this->toArray());
	}

	/**
	 * @param array<mixed> $keys
	 *
	 * @return array<string>
	 */
	protected function normalizeKeys(array $keys): array
	{
		return ($keys !== [] && is_array($keys[0])) ? $keys[0] : $keys;
	}

	/**
	 * @return void
	 */
	public function __clone()
	{
		$this->proxy = Obj::replicate($this->proxy);
	}

	/**
	 * @throws OutOfBoundsException
	 */
	public function __get(string $key): mixed
	{
		if (!$this->has($key)) {
			throw new OutOfBoundsException(sprintf('Key "%s" does not exist.', $key));
		}

		return $this->proxy->{$key};
	}

	public function __set(string $key, mixed $value): void
	{
		$this->set($key, $value);
	}

	public function __isset(string $key): bool
	{
		return $this->has($key);
	}

	public function __unset(string $key): void
	{
		$this->remove($key);
	}

}
