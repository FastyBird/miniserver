<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Mapping;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use FastyBird\Core\Values\Types as ValuesTypes;
use Throwable;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function preg_match;

/**
 * Valinor converter for the format of a property, which is a text, a list of texts, a pair that bounds a number range
 * or a list of rows of three that makes a combined enum. It reads what the rules of orisai read, in the order they
 * were tried, and keeps what it was given except for a blank text, which is null
 *
 * - a text with a non-whitespace character: kept; a blank text, and null: null
 * - a list of such texts
 * - a list of rows of three, each a number, a text, a pair or null, a pair being two numbers, texts or bools
 * - a pair, each a number, a pair of a short data type and a number, or null
 *
 * Every array has int keys, none below zero. Valinor takes none of this from the types: the formats are arrays of
 * arrays of the same sort of values, and it refuses a union of two array types because it cannot tell the one it holds
 * from the other. No array is more than one of the three (a row has three items, a range two, and a text is not an
 * array), but the empty one, which is the same in each, so the order they are tried in does not change the result
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
#[AsConverter]
final readonly class MapFormat
{

	private const array NUMBER_TYPES = [
		ValuesTypes\DataTypeShort::CHAR->value,
		ValuesTypes\DataTypeShort::UCHAR->value,
		ValuesTypes\DataTypeShort::SHORT->value,
		ValuesTypes\DataTypeShort::USHORT->value,
		ValuesTypes\DataTypeShort::INT->value,
		ValuesTypes\DataTypeShort::UINT->value,
		ValuesTypes\DataTypeShort::FLOAT->value,
	];

	/**
	 * @return string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null
	 *
	 * @throws Throwable
	 */
	public function map(mixed $value): string|array|null
	{
		if ($value === null) {
			return null;
		}

		if (is_string($value)) {
			return self::isNotBlank($value) ? $value : null;
		}

		$format = is_array($value) ? self::texts($value) ?? self::rows($value) ?? self::range($value) : null;

		if ($format === null) {
			throw MessageBuilder::newError('Value {source_value} is not a valid format.')->build();
		}

		return $format;
	}

	/**
	 * @param array<mixed> $value
	 *
	 * @return array<int, string>|null
	 */
	private static function texts(array $value): array|null
	{
		$texts = [];

		foreach ($value as $key => $item) {
			if (!is_int($key) || $key < 0 || !is_string($item) || !self::isNotBlank($item)) {
				return null;
			}

			$texts[$key] = $item;
		}

		return $texts;
	}

	/**
	 * @param array<mixed> $value
	 *
	 * @return array<int, array<int, array<int, bool|float|int|string>|float|int|string|null>>|null
	 */
	private static function rows(array $value): array|null
	{
		$rows = [];

		foreach ($value as $key => $row) {
			if (!is_int($key) || $key < 0 || !is_array($row) || count($row) !== 3) {
				return null;
			}

			$items = [];

			foreach ($row as $itemKey => $item) {
				if (!is_int($itemKey) || $itemKey < 0) {
					return null;
				}

				if (is_int($item) || is_float($item) || $item === null) {
					$items[$itemKey] = $item;
				} elseif (is_string($item)) {
					$items[$itemKey] = self::isNotBlank($item) ? $item : null;
				} elseif (is_array($item) && ($pair = self::pair($item, true)) !== null) {
					$items[$itemKey] = $pair;
				} else {
					return null;
				}
			}

			$rows[$key] = $items;
		}

		return $rows;
	}

	/**
	 * @param array<mixed> $value
	 *
	 * @return array<int, array<int, bool|float|int|string>|float|int|null>|null
	 */
	private static function range(array $value): array|null
	{
		if (count($value) !== 2) {
			return null;
		}

		$range = [];

		foreach ($value as $key => $item) {
			if (!is_int($key) || $key < 0) {
				return null;
			}

			if (is_int($item) || is_float($item) || $item === null) {
				$range[$key] = $item;
			} elseif (is_string($item) && !self::isNotBlank($item)) {
				$range[$key] = null;
			} elseif (is_array($item) && ($pair = self::pair($item, false)) !== null) {
				$range[$key] = $pair;
			} else {
				return null;
			}
		}

		return $range;
	}

	/**
	 * A data type, then a number, a text or a bool. The data types a pair of a combined enum is allowed are every one of
	 * them and its second item any text, a pair of a range takes the numeric data types only and numbers
	 *
	 * @param array<mixed> $value
	 *
	 * @return array<int, bool|float|int|string>|null
	 */
	private static function pair(array $value, bool $ofCombinedEnum): array|null
	{
		if (count($value) !== 2) {
			return null;
		}

		$pair = [];

		foreach ($value as $key => $item) {
			if (!is_int($key) || $key < 0) {
				return null;
			}

			if (
				is_int($item)
				|| is_float($item)
				|| ($ofCombinedEnum && (is_bool($item) || (is_string($item) && self::isNotBlank($item))))
				|| (!$ofCombinedEnum && is_string($item) && in_array($item, self::NUMBER_TYPES, true))
			) {
				$pair[$key] = $item;
			} else {
				return null;
			}
		}

		return $pair;
	}

	private static function isNotBlank(string $value): bool
	{
		return preg_match('/\S/', $value) === 1;
	}

}
