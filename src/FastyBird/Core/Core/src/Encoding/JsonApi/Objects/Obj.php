<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Exceptions;
use stdClass;
use Traversable;
use function array_keys;
use function get_object_vars;
use function is_array;
use function property_exists;

final class Obj
{

	/**
	 * @return string|int|float|bool|array<mixed>|IStandardObject|null
	 *
	 * @phpstan-return string|int|float|bool|array<mixed>|IStandardObject<string, string|int|float|bool|array<mixed>|null>|null
	 */
	public static function get(
		IStandardObject|stdClass $data,
		string $key,
		mixed $default = null,
	): string|int|float|bool|array|IStandardObject|null
	{
		if ($data instanceof IStandardObject) {
			return $data->get($key, $default);
		}

		if (!property_exists($data, $key)) {
			return $default;
		}

		$value = $data->{$key};

		if ($value instanceof IStandardObject || $value instanceof stdClass) {
			return self::cast($value);
		} elseif (is_array($value)) {
			$mapped = [];

			foreach ($value as $fieldKey => $field) {
				$mapped[$fieldKey] = $field instanceof IStandardObject || $field instanceof stdClass
					? self::cast($field)
					: $field;
			}

			return $mapped;
		}

		return $value;
	}

	/**
	 * @phpstan-return IStandardObject<string, string|int|float|bool|array<mixed>|null>
	 */
	public static function cast(IStandardObject|stdClass $data): IStandardObject
	{
		return $data instanceof IStandardObject ? $data : new StandardObject($data);
	}

	public static function replicate(stdClass $data): stdClass
	{
		$copy = clone $data;

		foreach (array_keys(get_object_vars($copy)) as $key) {
			if ($data->{$key} instanceof stdClass) {
				$copy->{$key} = self::replicate($data->{$key});
			}
		}

		return $copy;
	}

	/**
	 * @param stdClass|array<mixed> $data
	 *
	 * @return Traversable
	 *
	 * @phpstan-return Traversable<string, string|int|float|bool|array<mixed>|IStandardObject|null>
	 */
	public static function traverse(stdClass|array $data): Traversable
	{
		/** @phpstan-ignore-next-line */
		if (!$data instanceof stdClass && !is_array($data)) {
			throw new Exceptions\InvalidArgument('Expecting an object or array to transform keys.');
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				yield $key => $value instanceof stdClass ? self::cast($value) : $value;
			}
		} else {
			foreach (array_keys(get_object_vars($data)) as $key) {
				yield $key => $data->{$key} instanceof stdClass ? self::cast($data->{$key}) : $data->{$key};
			}
		}
	}

	/**
	 * @param stdClass|array<mixed> $data
	 *
	 * @return array<mixed>
	 *
	 * @phpstan-return Array<string, string|int|float|bool|array<mixed>|null>
	 */
	public static function toArray(stdClass|array $data): array
	{
		/** @phpstan-ignore-next-line */
		if (!$data instanceof stdClass && !is_array($data)) {
			throw new Exceptions\InvalidArgument('Expecting an object or array to transform keys.');
		}

		$arr = [];

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$arr[$key] = ($value instanceof stdClass || is_array($value)) ? self::toArray($value) : $value;
			}
		} else {
			foreach (array_keys(get_object_vars($data)) as $key) {
				$arr[$key] = ($data->{$key} instanceof stdClass || is_array($data->{$key}))
					? self::toArray($data->{$key})
					: $data->{$key};
			}
		}

		return $arr;
	}

}
