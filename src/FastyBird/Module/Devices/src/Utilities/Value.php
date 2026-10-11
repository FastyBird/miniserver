<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Core\Values\Types;
use function in_array;

/**
 * Useful value helpers
 */
final class Value
{

	public static function compareDataTypes(
		Types\DataType $left,
		Types\DataType $right,
	): bool
	{
		if ($left === $right) {
			return true;
		}

		return in_array(
			$left,
			[
				Types\DataType::CHAR,
				Types\DataType::UCHAR,
				Types\DataType::SHORT,
				Types\DataType::USHORT,
				Types\DataType::INT,
				Types\DataType::UINT,
				Types\DataType::FLOAT,
			],
			true,
		)
			&& in_array(
				$right,
				[
					Types\DataType::CHAR,
					Types\DataType::UCHAR,
					Types\DataType::SHORT,
					Types\DataType::USHORT,
					Types\DataType::INT,
					Types\DataType::UINT,
					Types\DataType::FLOAT,
				],
				true,
			);
	}

}
