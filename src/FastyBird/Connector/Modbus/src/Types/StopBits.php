<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Communication stop bits types
 */
enum StopBits: int
{

	case NONE = 0;

	case ONE = 1;

	case TWO = 2;

}
