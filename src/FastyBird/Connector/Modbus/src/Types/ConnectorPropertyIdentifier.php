<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * Connector property identifier types
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case RTU_INTERFACE = 'rtu_interface';

	case RTU_BYTE_SIZE = 'rtu_byte_size';

	case RTU_BAUD_RATE = 'rtu_baud_rate';

	case RTU_PARITY = 'rtu_parity';

	case RTU_STOP_BITS = 'rtu_stop_bits';

}
