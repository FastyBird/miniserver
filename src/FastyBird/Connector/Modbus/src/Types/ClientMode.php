<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Connector client mode types
 */
enum ClientMode: string
{

	case RTU = 'rtu';

	case TCP = 'tcp';

}
