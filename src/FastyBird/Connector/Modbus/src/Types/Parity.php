<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Communication stop bits types
 */
enum Parity: int
{

	case NONE = 0;

	case ODD = 1;

	case EVEN = 2;

}
