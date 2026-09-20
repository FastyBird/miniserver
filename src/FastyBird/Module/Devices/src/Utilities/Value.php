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

use FastyBird\Core\Types\Metadata as MetadataTypes;
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
		MetadataTypes\DataType $left,
		MetadataTypes\DataType $right,
	): bool
	{
		if ($left === $right) {
			return true;
		}

		return in_array(
			$left,
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
			&& in_array(
				$right,
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
			);
	}

}
