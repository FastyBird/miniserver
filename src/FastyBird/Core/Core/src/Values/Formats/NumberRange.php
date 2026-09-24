<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Formats;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Types;
use Nette\Utils;
use Override;
use TypeError;
use ValueError;
use function array_map;
use function count;
use function explode;
use function floatval;
use function implode;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function strval;
use function trim;

/**
 * String enum value format
 */
final class NumberRange
{

	private int|float|null $min = null;

	private int|float|null $max = null;

	private Types\DataTypeShort|null $minDataType = null;

	private Types\DataTypeShort|null $maxDataType = null;

	/**
	 * @param string|array<int, string|int|float|array<int, string|int|float>|null> $format
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function __construct(string|array $format)
	{
		if (is_string($format) && str_contains($format, ':')) {
			$items = explode(':', $format) + [null, null];

			if (is_string($items[0])) {
				if (str_contains($items[0], '|')) {
					$parts = explode('|', $items[0]) + [null, null];

					if (
						$parts[0] === null
						|| Types\DataTypeShort::tryFrom(Utils\Strings::lower($parts[0])) === null
					) {
						throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
					}

					$this->min = $parts[1] !== null && trim($parts[1]) !== '' ? floatval($parts[1]) : null;
					$this->minDataType = Types\DataTypeShort::from(Utils\Strings::lower($parts[0]));

				} elseif (trim($items[0]) !== '') {
					$this->min = floatval($items[0]);
					$this->minDataType = null;

				} else {
					$this->min = null;
					$this->minDataType = null;
				}
			} else {
				$this->min = null;
			}

			if (is_string($items[1])) {
				if (str_contains($items[1], '|')) {
					$parts = explode('|', $items[1]) + [null, null];

					if (
						$parts[0] === null
						|| Types\DataTypeShort::tryFrom(Utils\Strings::lower($parts[0])) === null
					) {
						throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
					}

					$this->max = $parts[1] !== null && trim($parts[1]) !== '' ? floatval($parts[1]) : null;
					$this->maxDataType = Types\DataTypeShort::from(Utils\Strings::lower($parts[0]));

				} elseif (trim($items[1]) !== '') {
					$this->max = floatval($items[1]);
					$this->maxDataType = null;

				} else {
					$this->max = null;
					$this->maxDataType = null;
				}
			} else {
				$this->max = null;
			}
		} elseif (is_array($format) && count($format) === 2) {
			foreach ($format as $item) {
				if (!$this->checkItem($item)) {
					throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
				}
			}

			if (is_array($format[0]) && count($format[0]) === 2) {
				$this->minDataType = Types\DataTypeShort::from(Utils\Strings::lower(strval($format[0][0])));
				$this->min = is_numeric($format[0][1]) ? floatval($format[0][1]) : null;
			} else {
				$this->min = is_numeric($format[0]) ? floatval($format[0]) : null;
			}

			if (is_array($format[1]) && count($format[1]) === 2) {
				$this->maxDataType = Types\DataTypeShort::from(Utils\Strings::lower(strval($format[1][0])));
				$this->max = is_numeric($format[1][1]) ? floatval($format[1][1]) : null;
			} else {
				$this->max = is_numeric($format[1]) ? floatval($format[1]) : null;
			}
		} else {
			throw new Exceptions\InvalidArgument('Provided format is not valid for number range format');
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getMin(): float|int|null
	{
		if ($this->getMinDataType() !== null) {
			if (
				$this->getMinDataType() === Types\DataTypeShort::CHAR
				|| $this->getMinDataType() === Types\DataTypeShort::UCHAR
				|| $this->getMinDataType() === Types\DataTypeShort::SHORT
				|| $this->getMinDataType() === Types\DataTypeShort::USHORT
				|| $this->getMinDataType() === Types\DataTypeShort::INT
				|| $this->getMinDataType() === Types\DataTypeShort::UINT
			) {
				return intval($this->min);
			} elseif ($this->getMinDataType() === Types\DataTypeShort::FLOAT) {
				return floatval($this->min);
			}

			throw new Exceptions\InvalidState('Value is in invalid state');
		}

		return $this->min;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getMax(): float|int|null
	{
		if ($this->getMaxDataType() !== null) {
			if (
				$this->getMaxDataType() === Types\DataTypeShort::CHAR
				|| $this->getMaxDataType() === Types\DataTypeShort::UCHAR
				|| $this->getMaxDataType() === Types\DataTypeShort::SHORT
				|| $this->getMaxDataType() === Types\DataTypeShort::USHORT
				|| $this->getMaxDataType() === Types\DataTypeShort::INT
				|| $this->getMaxDataType() === Types\DataTypeShort::UINT
			) {
				return intval($this->max);
			} elseif ($this->getMaxDataType() === Types\DataTypeShort::FLOAT) {
				return floatval($this->max);
			}

			throw new Exceptions\InvalidState('Value is in invalid state');
		}

		return $this->max;
	}

	public function getMinDataType(): Types\DataTypeShort|null
	{
		return $this->min !== null ? $this->minDataType : null;
	}

	public function getMaxDataType(): Types\DataTypeShort|null
	{
		return $this->max !== null ? $this->maxDataType : null;
	}

	/**
	 * @return array<int, int|float|array<int, string|int|float|null>|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toArray(): array
	{
		return [
			($this->getMinDataType() !== null
				? [$this->getMinDataType()->value, $this->getMin()]
				: $this->getMin()
			),
			($this->getMaxDataType() !== null
				? [$this->getMaxDataType()->value, $this->getMax()]
				: $this->getMax()
			),
		];
	}

	/**
	 * @return array<int, int|float|array<int, string|int|float|null>|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(): array
	{
		return $this->toArray();
	}

	/**
	 * @param int|float|array<int, string|int|float>|null $item
	 */
	private function checkItem(string|int|float|array|null $item): bool
	{
		return (
				is_array($item)
				&& count($item) === 2
				&& is_string($item[0])
				&& Types\DataTypeShort::tryFrom(Utils\Strings::lower($item[0])) !== null
				&& is_numeric($item[1])
			)
			|| is_numeric($item)
			|| is_string($item)
			|| $item === null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function __toString(): string
	{
		return implode(':', array_map(static function (int|float|array|null $item): string|int|float|null {
			if (is_array($item)) {
				return implode('|', $item);
			}

			return $item;
		}, $this->toArray()));
	}

}
