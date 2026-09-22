<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use Countable;
use JsonSerializable;
use stdClass;
use Traversable;

/**
 * Standard object interface
 *
 * @phpstan-extends Traversable<string, mixed>
 */
interface IStandardObject extends Traversable, Countable, JsonSerializable
{

	/**
	 * @return string|int|float|bool|array<mixed>|IStandardObject|null
	 *
	 * @phpstan-return string|int|float|bool|array<mixed>|IStandardObject<string, string|int|float|bool|array<mixed>|null>|null
	 */
	public function get(string $key, mixed $default = null): string|int|float|bool|array|self|null;

	/**
	 * @param string|array<string> ...$keys
	 *
	 * @return array<mixed>
	 *
	 * @phpstan-return Array<string|int|float|bool|array<mixed>|IStandardObject<string, string|int|float|bool|array<mixed>|null>>
	 */
	public function getMany(string|array ...$keys): array;

	/**
	 * @return IStandardObject
	 *
	 * @phpstan-param string|int|float|bool|array<mixed>|IStandardObject<string, string|int|float|bool|array<mixed>|null>|null $value
	 *
	 * @phpstan-return IStandardObject<string, string|int|float|bool|array<mixed>|null>
	 */
	public function set(string $key, mixed $value): self;

	/**
	 * @param array<mixed> $values
	 *
	 * @return IStandardObject
	 *
	 * @phpstan-param Array<string, string|int|float|bool|array<mixed>|IStandardObject<string, string|int|float|bool|array<mixed>|null>> $values
	 *
	 * @phpstan-return IStandardObject<string, string|int|float|bool|array<mixed>|null>
	 */
	public function setMany(array $values): self;

	public function has(string $key): bool;

	/**
	 * @param array<string> ...$keys
	 */
	public function hasAny(array ...$keys): bool;

	/**
	 * @return array<string>
	 */
	public function keys(): array;

	/**
	 * @return IStandardObject
	 *
	 * @phpstan-return IStandardObject<string, string|int|float|bool|array<mixed>|null>
	 */
	public function copy(): self;

	/**
	 * @param array<string> ...$key
	 *
	 * @return IStandardObject
	 *
	 * @phpstan-return IStandardObject<string, string|int|float|bool|array<mixed>|null>
	 */
	public function remove(array ...$key): self;

	public function toStdClass(): stdClass;

	/**
	 * @return array<mixed>
	 */
	public function toArray(): array;

}
