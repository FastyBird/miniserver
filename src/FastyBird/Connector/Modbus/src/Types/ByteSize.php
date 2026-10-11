<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Communication data bits types
 */
enum ByteSize: int
{

	case SIZE_4 = 4; // win

	case SIZE_5 = 5;

	case SIZE_6 = 6;

	case SIZE_7 = 7;

	case SIZE_8 = 8;

}
