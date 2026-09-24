<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Utilities;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types;
use Throwable;
use function floatval;
use function intval;
use function sprintf;

/**
 * Data type helpers
 */
final class DataType
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public static function inferNumberDataType(
		Formats\NumberRange $format,
		float|int|null $step = null,
		Types\DataType|null $fallback = null,
	): Types\DataType
	{
		if (
			$format->getMinDataType() !== null
			|| $format->getMaxDataType() !== null
		) {
			return match ($format->getMinDataType() ?? $format->getMaxDataType()) {
				Types\DataTypeShort::CHAR => Types\DataType::CHAR,
				Types\DataTypeShort::UCHAR => Types\DataType::UCHAR,
				Types\DataTypeShort::SHORT => Types\DataType::SHORT,
				Types\DataTypeShort::USHORT => Types\DataType::USHORT,
				Types\DataTypeShort::INT => Types\DataType::INT,
				Types\DataTypeShort::UINT => Types\DataType::UINT,
				Types\DataTypeShort::FLOAT => Types\DataType::FLOAT,
				default => Types\DataType::UNKNOWN,
			};
		}

		if (
			$step !== null
			// If step is defined and is float number, data type have to be float
			&& floatval(intval($step)) !== $step
		) {
			return Types\DataType::FLOAT;
		}

		if (
			(
				$format->getMin() !== null
				// If minimum value is defined and is float number, data type have to be float
				&& floatval(intval($format->getMin())) !== $format->getMin()
			) || (
				$format->getMax() !== null
				// If maximum value is defined and is float number, data type have to be float
				&& floatval(intval($format->getMax())) !== $format->getMax()
			)
		) {
			return Types\DataType::FLOAT;
		}

		if ($format->getMin() !== null || $format->getMax() !== null) {
			$dataTypeRanges = [
				Types\DataType::CHAR->value => [-128, 127],
				Types\DataType::UCHAR->value => [0, 255],
				Types\DataType::SHORT->value => [-32_768, 32_767],
				Types\DataType::USHORT->value => [0, 65_535],
				Types\DataType::INT->value => [-2_147_483_648, 2_147_483_647],
				Types\DataType::UINT->value => [0, 4_294_967_295],
			];

			foreach ($dataTypeRanges as $dataType => $ranges) {
				if (
					(
						$format->getMin() === null
						|| (
							$format->getMin() >= $ranges[0]
							&& $format->getMin() <= $ranges[1]
						)
					) && (
						$format->getMax() === null
						|| (
							$format->getMax() >= $ranges[0]
							&& $format->getMax() <= $ranges[1]
						)
					)
				) {
					try {
						return Types\DataType::from($dataType);
					} catch (Throwable) {
						throw new Exceptions\InvalidState(sprintf('Data type: %s could not be initialized', $dataType));
					}
				}
			}

			return Types\DataType::FLOAT;
		}

		return $fallback ?? Types\DataType::UNKNOWN;
	}

}
