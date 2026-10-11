<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 */
enum DevicePropertyIdentifier: string
{

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	case MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	case SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	case PORT = 'port';

	case ENCRYPTED = 'encrypted';

	case APP_ID = 'app_id';

	case ENCRYPTION_KEY = 'encryption_key';

	case STATE_READING_DELAY = 'state_reading_delay';

}
