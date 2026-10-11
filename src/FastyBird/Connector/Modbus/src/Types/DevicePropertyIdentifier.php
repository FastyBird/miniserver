<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 */
enum DevicePropertyIdentifier: string
{

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case ADDRESS = DevicesTypes\DevicePropertyIdentifier::ADDRESS->value;

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case PORT = 'port';

	case BYTE_ORDER = 'byte_order';

	case UNIT_ID = 'unit_id';

}
