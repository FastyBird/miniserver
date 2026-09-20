<?php declare(strict_types = 1);

/**
 * DataType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           24.09.18
 */

namespace FastyBird\Core\Types\Metadata;

use function in_array;

/**
 * Device or channel property data types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DataType: string
{

	case CHAR = 'char';

	case UCHAR = 'uchar';

	case SHORT = 'short';

	case USHORT = 'ushort';

	case INT = 'int';

	case UINT = 'uint';

	case FLOAT = 'float';

	case BOOLEAN = 'bool';

	case STRING = 'string';

	case ENUM = 'enum';

	case DATE = 'date';

	case TIME = 'time';

	case DATETIME = 'datetime';

	case COLOR = 'color';

	case BUTTON = 'button';

	case SWITCH = 'switch';

	case COVER = 'cover';

	case UNKNOWN = 'unknown';

	public function isInteger(): bool
	{
		return in_array(
			$this,
			[
				self::CHAR,
				self::UCHAR,
				self::SHORT,
				self::USHORT,
				self::INT,
				self::UINT,
			],
			true,
		);
	}

}
