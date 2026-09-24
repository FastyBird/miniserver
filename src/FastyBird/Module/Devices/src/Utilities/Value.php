<?php declare(strict_types = 1);

/**
 * Value.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           16.01.24
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Core\Values\Types;
use function in_array;

/**
 * Useful value helpers
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
