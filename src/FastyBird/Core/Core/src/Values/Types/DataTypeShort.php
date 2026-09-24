<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types;

use function in_array;

/**
 * Device or channel property data types
 */
enum DataTypeShort: string
{

	case CHAR = 'i8';

	case UCHAR = 'u8';

	case SHORT = 'i16';

	case USHORT = 'u16';

	case INT = 'i32';

	case UINT = 'u32';

	case FLOAT = 'f';

	case BOOLEAN = 'b';

	case STRING = 's';

	case ENUM = 'e';

	case DATE = 'd';

	case TIME = 't';

	case DATETIME = 'dt';

	case BUTTON = 'btn';

	case SWITCH = 'sw';

	case COVER = 'cvr';

	case UNKNOWN = 'unk';

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
