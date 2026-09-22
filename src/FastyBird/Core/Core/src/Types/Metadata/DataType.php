<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\Metadata;

use function in_array;

/**
 * Device or channel property data types
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
