<?php declare(strict_types = 1);

/**
 * StandardObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.0.1
 *
 * @date           17.03.20
 */

namespace FastyBird\Library\JsonApi\Objects;

use IteratorAggregate;
use OutOfBoundsException;
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
class StandardObject implements IteratorAggregate, IStandardObject
{

	protected stdClass $proxy;

	public function __construct(stdClass|null $proxy = null)
	{
		$this->proxy = $proxy ?? new stdClass();
	}

	public function get(string $key, mixed $default = null): string|int|float|bool|array|self|null
	{
		return Obj::get($this->proxy, $key, $default);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMany(string|array ...$keys): array
	{
		$values = [];

		foreach ($this->normalizeKeys($keys) as $key) {
			$values[$key] = $this->has($key) ? $this->proxy->{$key} : null;
		}

		return $values;
	}

	public function set(string $key, mixed $value): IStandardObject
	{
		$this->proxy->{$key} = $value;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMany(array $values): IStandardObject
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value);
		}

		return $this;
	}

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
	public function keys(): array
	{
		return array_keys(get_object_vars($this->proxy));
	}

	public function copy(): IStandardObject
	{
		return clone $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(array ...$keys): IStandardObject
	{
		foreach ($this->normalizeKeys($keys) as $key) {
			unset($this->proxy->{$key});
		}

		return $this;
	}

	public function toStdClass(): stdClass
	{
		return Obj::replicate($this->proxy);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return Obj::toArray($this->proxy);
	}

	public function jsonSerialize(): stdClass
	{
		return $this->proxy;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @phpstan-return Traversable<string, mixed>
	 */
	public function getIterator(): Traversable
	{
		return Obj::traverse($this->proxy);
	}

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
