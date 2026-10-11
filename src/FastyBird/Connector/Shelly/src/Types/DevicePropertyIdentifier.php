<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifiers
 */
enum DevicePropertyIdentifier: string
{

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	case FIRMWARE_VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	case SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	case DOMAIN = 'domain';

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case AUTH_ENABLED = 'auth_enabled';

	case GENERATION = 'generation';

	case STATE_READING_DELAY = 'state_reading_delay';

}
