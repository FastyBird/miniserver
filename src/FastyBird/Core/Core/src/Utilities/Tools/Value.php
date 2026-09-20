<?php declare(strict_types = 1);

/**
 * Value.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           05.12.20
 */

namespace FastyBird\Core\Utilities\Tools;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Formats\Tools as Formats;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function round;
use function sprintf;
use function strval;

/**
 * Value helpers
 *
 * @package        FastyBird:Tools!
 * @package        FastyBird:Tools!
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Value
{

	private const DATE_FORMAT = 'Y-m-d';

	private const TIME_FORMAT = 'H:i:sP';

	private const BOOL_TRUE_VALUES = ['true', 't', 'yes', 'y', '1', 'on'];

	/**
	 * Purpose of this method is to convert value to defined data type
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function normalizeValue(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		MetadataTypes\DataType $dataType,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			$dataType === MetadataTypes\DataType::CHAR
			|| $dataType === MetadataTypes\DataType::UCHAR
			|| $dataType === MetadataTypes\DataType::SHORT
			|| $dataType === MetadataTypes\DataType::USHORT
			|| $dataType === MetadataTypes\DataType::INT
			|| $dataType === MetadataTypes\DataType::UINT
		) {
			$value = intval(self::flattenValue($value));

			if (
				$format instanceof Formats\NumberRange
				&& (
					(
						$format->getMin() !== null
						&& intval($format->getMin()) > $value
					) || (
						$format->getMax() !== null
						&& intval($format->getMax()) < $value
					)
				)
			) {
				throw new ToolsExceptions\InvalidValue(sprintf(
					'Provided value: "%d" is out of allowed value range: [%s, %s]',
					$value,
					self::toString($format->getMin()),
					self::toString($format->getMax()),
				));
			}

			return $value;
		} elseif ($dataType === MetadataTypes\DataType::FLOAT) {
			$value = floatval(self::flattenValue($value));

			if (
				$format instanceof Formats\NumberRange
				&& (
					(
						$format->getMin() !== null
						&& floatval($format->getMin()) > $value
					) || (
						$format->getMax() !== null
						&& floatval($format->getMax()) < $value
					)
				)
			) {
				throw new ToolsExceptions\InvalidValue(sprintf(
					'Provided value: "%f" is out of allowed value range: [%s, %s]',
					$value,
					self::toString($format->getMin()),
					self::toString($format->getMax()),
				));
			}

			return $value;
		} elseif ($dataType === MetadataTypes\DataType::STRING) {
			return self::toString($value);
		} elseif ($dataType === MetadataTypes\DataType::BOOLEAN) {
			return in_array(
				Utils\Strings::lower(self::toString($value, true)),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType === MetadataTypes\DataType::DATE) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, self::toString($value, true));

			return $value === false ? null : $value;
		} elseif ($dataType === MetadataTypes\DataType::TIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, self::toString($value, true));

			return $value === false ? null : $value;
		} elseif ($dataType === MetadataTypes\DataType::DATETIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, self::toString($value, true));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					self::toString($value, true),
				);
			}

			return $formatted === false ? null : $formatted;
		} elseif (
			$dataType === MetadataTypes\DataType::BUTTON
			|| $dataType === MetadataTypes\DataType::SWITCH
			|| $dataType === MetadataTypes\DataType::COVER
			|| $dataType === MetadataTypes\DataType::ENUM
		) {
			/** @var class-string<MetadataTypes\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === MetadataTypes\DataType::BUTTON) {
				$payloadClass = MetadataTypes\Payloads\Button::class;
			} elseif ($dataType === MetadataTypes\DataType::SWITCH) {
				$payloadClass = MetadataTypes\Payloads\Switcher::class;
			} elseif ($dataType === MetadataTypes\DataType::COVER) {
				$payloadClass = MetadataTypes\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return $payloadClass !== null
						&& (
							$dataType === MetadataTypes\DataType::BUTTON
							|| $dataType === MetadataTypes\DataType::SWITCH
							|| $dataType === MetadataTypes\DataType::COVER
						)
					 ? $payloadClass::from(self::toString($value, true)) : self::toString($value, true);
				}

				throw new ToolsExceptions\InvalidValue(
					sprintf(
						'Provided value: "%s" is not in valid rage: [%s]',
						self::toString($value),
						implode(', ', $format->toArray()),
					),
				);
			} elseif ($format instanceof Formats\CombinedEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[0] === null) {
							return false;
						}

						return self::compareValues(
							$item[0]->getValue(),
							self::normalizeEnumItemValue($value, $item[0]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof Formats\CombinedEnumItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType === MetadataTypes\DataType::BUTTON
							|| $dataType === MetadataTypes\DataType::SWITCH
							|| $dataType === MetadataTypes\DataType::COVER
						)
					) {
						return $payloadClass::from(self::toString($filtered[0][0]->getValue(), true));
					}

					return self::toString($filtered[0][0]->getValue());
				}

				try {
					throw new ToolsExceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage: [%s]',
							self::toString($value),
							Utils\Json::encode($format->toArray()),
						),
					);
				} catch (Utils\JsonException $ex) {
					throw new ToolsExceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage. Value format could not be converted to error',
							self::toString($value),
						),
						$ex->getCode(),
						$ex,
					);
				}
			} else {
				if (
					$payloadClass !== null
					&& (
						$dataType === MetadataTypes\DataType::BUTTON
						|| $dataType === MetadataTypes\DataType::SWITCH
						|| $dataType === MetadataTypes\DataType::COVER
					)
				) {
					if ($payloadClass::tryFrom(self::toString($value, true)) !== null) {
						return $payloadClass::from(self::toString($value, true));
					}

					throw new ToolsExceptions\InvalidValue(
						sprintf(
							'Provided value: "%s" is not in valid rage: [%s]',
							self::toString($value),
							implode(
								', ',
								array_map(
									static fn (MetadataTypes\Payloads\Payload $enum): string => strval($enum->value),
									$payloadClass::cases(),
								),
							),
						),
					);
				}

				return self::toString($value);
			}
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function transformValueFromDevice(
		bool|int|float|string|null $value,
		MetadataTypes\DataType $dataType,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|MetadataTypes\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType === MetadataTypes\DataType::BOOLEAN) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType === MetadataTypes\DataType::FLOAT) {
			return floatval($value);
		}

		if (
			$dataType === MetadataTypes\DataType::UCHAR
			|| $dataType === MetadataTypes\DataType::CHAR
			|| $dataType === MetadataTypes\DataType::USHORT
			|| $dataType === MetadataTypes\DataType::SHORT
			|| $dataType === MetadataTypes\DataType::UINT
			|| $dataType === MetadataTypes\DataType::INT
		) {
			return intval($value);
		}

		if ($dataType === MetadataTypes\DataType::STRING) {
			return strval($value);
		}

		if (
			$dataType === MetadataTypes\DataType::BUTTON
			|| $dataType === MetadataTypes\DataType::SWITCH
			|| $dataType === MetadataTypes\DataType::COVER
			|| $dataType === MetadataTypes\DataType::ENUM
		) {
			/** @var class-string<MetadataTypes\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === MetadataTypes\DataType::BUTTON) {
				$payloadClass = MetadataTypes\Payloads\Button::class;
			} elseif ($dataType === MetadataTypes\DataType::SWITCH) {
				$payloadClass = MetadataTypes\Payloads\Switcher::class;
			} elseif ($dataType === MetadataTypes\DataType::COVER) {
				$payloadClass = MetadataTypes\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					if (
						$payloadClass !== null
						&& (
							$dataType === MetadataTypes\DataType::BUTTON
							|| $dataType === MetadataTypes\DataType::SWITCH
							|| $dataType === MetadataTypes\DataType::COVER
						)
					) {
						return $payloadClass::from(self::toString($value, true));
					}

					return strval($value);
				}

				return null;
			} elseif ($format instanceof Formats\CombinedEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[1] === null) {
							return false;
						}

						return self::compareValues(
							$item[1]->getValue(),
							self::normalizeEnumItemValue($value, $item[1]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][0] instanceof Formats\CombinedEnumItem
				) {
					if (
						$payloadClass !== null
						&& (
							$dataType === MetadataTypes\DataType::BUTTON
							|| $dataType === MetadataTypes\DataType::SWITCH
							|| $dataType === MetadataTypes\DataType::COVER
						)
					) {
						return $payloadClass::from(self::toString($filtered[0][0]->getValue(), true));
					}

					return self::toString($filtered[0][0]->getValue());
				}

				return null;
			} else {
				if ($payloadClass !== null && $payloadClass::tryFrom(self::toString($value, true)) !== null) {
					return $payloadClass::from(self::toString($value, true));
				}
			}
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public static function transformValueToDevice(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		MetadataTypes\DataType $dataType,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format,
	): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType === MetadataTypes\DataType::BOOLEAN) {
			if (is_bool($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType === MetadataTypes\DataType::FLOAT) {
			if (is_numeric($value)) {
				return floatval($value);
			}

			return null;
		}

		if (
			$dataType === MetadataTypes\DataType::UCHAR
			|| $dataType === MetadataTypes\DataType::CHAR
			|| $dataType === MetadataTypes\DataType::USHORT
			|| $dataType === MetadataTypes\DataType::SHORT
			|| $dataType === MetadataTypes\DataType::UINT
			|| $dataType === MetadataTypes\DataType::INT
		) {
			if (is_numeric($value)) {
				return intval($value);
			}

			return null;
		}

		if ($dataType === MetadataTypes\DataType::STRING) {
			if (is_string($value)) {
				return $value;
			}

			return null;
		}

		if ($dataType === MetadataTypes\DataType::DATE) {
			if ($value instanceof DateTime) {
				return $value->format(self::DATE_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, self::toString($value, true));

			return $value === false ? null : $value->format(self::DATE_FORMAT);
		}

		if ($dataType === MetadataTypes\DataType::TIME) {
			if ($value instanceof DateTime) {
				return $value->format(self::TIME_FORMAT);
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, self::toString($value, true));

			return $value === false ? null : $value->format(self::TIME_FORMAT);
		}

		if ($dataType === MetadataTypes\DataType::DATETIME) {
			if ($value instanceof DateTime) {
				return $value->format(DateTimeInterface::ATOM);
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, self::toString($value, true));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					self::toString($value, true),
				);
			}

			return $formatted === false ? null : $formatted->format(DateTimeInterface::ATOM);
		}

		if (
			$dataType === MetadataTypes\DataType::BUTTON
			|| $dataType === MetadataTypes\DataType::SWITCH
			|| $dataType === MetadataTypes\DataType::COVER
			|| $dataType === MetadataTypes\DataType::ENUM
		) {
			/** @var class-string<MetadataTypes\Payloads\Payload>|null $payloadClass */
			$payloadClass = null;

			if ($dataType === MetadataTypes\DataType::BUTTON) {
				$payloadClass = MetadataTypes\Payloads\Button::class;
			} elseif ($dataType === MetadataTypes\DataType::SWITCH) {
				$payloadClass = MetadataTypes\Payloads\Switcher::class;
			} elseif ($dataType === MetadataTypes\DataType::COVER) {
				$payloadClass = MetadataTypes\Payloads\Cover::class;
			}

			if ($format instanceof Formats\StringEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static fn (string $item): bool => self::compareValues($value, $item),
				));

				if (count($filtered) === 1) {
					return self::toString($value);
				}

				return null;
			} elseif ($format instanceof Formats\CombinedEnum) {
				$filtered = array_values(array_filter(
					$format->getItems(),
					static function (array $item) use ($value): bool {
						if ($item[0] === null) {
							return false;
						}

						return self::compareValues(
							$item[0]->getValue(),
							self::normalizeEnumItemValue($value, $item[0]->getDataType()),
						);
					},
				));

				if (
					count($filtered) === 1
					&& $filtered[0][2] instanceof Formats\CombinedEnumItem
				) {
					return self::flattenValue($filtered[0][2]->getValue());
				}

				return null;
			} else {
				if ($payloadClass !== null) {
					if ($value instanceof $payloadClass) {
						return $value->value;
					}

					return $payloadClass::tryFrom(self::toString($value, true)) !== null
						? self::toString($value)
						: null;
				}
			}
		}

		return self::flattenValue($value);
	}

	public static function transformToScale(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		MetadataTypes\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array(
				$dataType,
				[
					MetadataTypes\DataType::CHAR,
					MetadataTypes\DataType::UCHAR,
					MetadataTypes\DataType::SHORT,
					MetadataTypes\DataType::USHORT,
					MetadataTypes\DataType::INT,
					MetadataTypes\DataType::UINT,
					MetadataTypes\DataType::FLOAT,
				],
				true,
			)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($scale !== null) {
				$value = intval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value /= 10;
				}

				$value = round(floatval($value), $scale);

				$value = $dataType === MetadataTypes\DataType::FLOAT
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function transformFromScale(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		MetadataTypes\DataType $dataType,
		int|null $scale = null,
	): bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($value === null) {
			return null;
		}

		if (
			in_array(
				$dataType,
				[
					MetadataTypes\DataType::CHAR,
					MetadataTypes\DataType::UCHAR,
					MetadataTypes\DataType::SHORT,
					MetadataTypes\DataType::USHORT,
					MetadataTypes\DataType::INT,
					MetadataTypes\DataType::UINT,
					MetadataTypes\DataType::FLOAT,
				],
				true,
			)
			&& (
				is_int($value)
				|| is_float($value)
			)
		) {
			if ($scale !== null) {
				$value = floatval($value);

				for ($i = 0; $i < $scale; $i++) {
					$value *= 10;
				}

				$value = round(floatval($value));

				$value = $dataType === MetadataTypes\DataType::FLOAT
					? $value
					: intval($value);
			}
		}

		return $value;
	}

	public static function flattenValue(
		bool|int|float|string|DateTimeInterface|BackedEnum|null $value,
	): bool|int|float|string|null
	{
		if ($value instanceof DateTimeInterface) {
			return $value->format(DateTimeInterface::ATOM);
		} elseif ($value instanceof BackedEnum) {
			return $value->value;
		}

		return $value;
	}

	/**
	 * @return ($throw is true ? string : string|null)
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function toString(
		bool|int|float|string|DateTimeInterface|BackedEnum|null $value,
		bool $throw = false,
	): string|null
	{
		$value = self::flattenValue($value);

		if ($throw && $value === null) {
			throw new Exceptions\InvalidArgument('Nullable value could not be converted to string.');
		}

		return $value !== null ? strval($value) : null;
	}

	public static function transformDataType(
		bool|int|float|string|null $value,
		MetadataTypes\DataType $dataType,
	): bool|int|float|string|null
	{
		if ($value === null) {
			return null;
		}

		if ($dataType === MetadataTypes\DataType::BOOLEAN) {
			return in_array(Utils\Strings::lower(strval($value)), self::BOOL_TRUE_VALUES, true);
		}

		if ($dataType === MetadataTypes\DataType::FLOAT) {
			return floatval($value);
		}

		if (
			$dataType === MetadataTypes\DataType::UCHAR
			|| $dataType === MetadataTypes\DataType::CHAR
			|| $dataType === MetadataTypes\DataType::USHORT
			|| $dataType === MetadataTypes\DataType::SHORT
			|| $dataType === MetadataTypes\DataType::UINT
			|| $dataType === MetadataTypes\DataType::INT
		) {
			return intval($value);
		}

		return strval($value);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private static function normalizeEnumItemValue(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		MetadataTypes\DataTypeShort|null $dataType,
	): bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($dataType === null) {
			return $value;
		}

		if (
			$dataType === MetadataTypes\DataTypeShort::CHAR
			|| $dataType === MetadataTypes\DataTypeShort::UCHAR
			|| $dataType === MetadataTypes\DataTypeShort::SHORT
			|| $dataType === MetadataTypes\DataTypeShort::USHORT
			|| $dataType === MetadataTypes\DataTypeShort::INT
			|| $dataType === MetadataTypes\DataTypeShort::UINT
		) {
			return intval(self::flattenValue($value));
		} elseif ($dataType === MetadataTypes\DataTypeShort::FLOAT) {
			return floatval(self::flattenValue($value));
		} elseif ($dataType === MetadataTypes\DataTypeShort::STRING) {
			return self::toString($value);
		} elseif ($dataType === MetadataTypes\DataTypeShort::BOOLEAN) {
			return in_array(
				Utils\Strings::lower(self::toString($value, true)),
				self::BOOL_TRUE_VALUES,
				true,
			);
		} elseif ($dataType === MetadataTypes\DataTypeShort::BUTTON) {
			if ($value instanceof MetadataTypes\Payloads\Button) {
				return $value;
			}

			return MetadataTypes\Payloads\Button::tryFrom(self::toString($value, true)) ?? false;
		} elseif ($dataType === MetadataTypes\DataTypeShort::SWITCH) {
			if ($value instanceof MetadataTypes\Payloads\Switcher) {
				return $value;
			}

			return MetadataTypes\Payloads\Switcher::tryFrom(self::toString($value, true)) ?? false;
		} elseif ($dataType === MetadataTypes\DataTypeShort::COVER) {
			if ($value instanceof MetadataTypes\Payloads\Cover) {
				return $value;
			}

			return MetadataTypes\Payloads\Cover::tryFrom(self::toString($value, true)) ?? false;
		} elseif ($dataType === MetadataTypes\DataTypeShort::DATE) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::DATE_FORMAT, self::toString($value, true));

			return $value === false ? null : $value;
		} elseif ($dataType === MetadataTypes\DataTypeShort::TIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$value = Utils\DateTime::createFromFormat(self::TIME_FORMAT, self::toString($value, true));

			return $value === false ? null : $value;
		} elseif ($dataType === MetadataTypes\DataTypeShort::DATETIME) {
			if ($value instanceof DateTime) {
				return $value;
			}

			$formatted = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, self::toString($value, true));

			if ($formatted === false) {
				$formatted = Utils\DateTime::createFromFormat(
					DateTimeInterface::RFC3339_EXTENDED,
					self::toString($value, true),
				);
			}

			return $formatted === false ? null : $formatted;
		}

		return $value;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private static function compareValues(
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $left,
		bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $right,
	): bool
	{
		if ($left === $right) {
			return true;
		}

		$left = Utils\Strings::lower(self::toString($left, true));
		$right = Utils\Strings::lower(self::toString($right, true));

		return $left === $right;
	}

}
