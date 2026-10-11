<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 */
enum DevicePropertyIdentifier: string
{

	case CATEGORY = 'category';

	case TYPE = 'type';

	case AID = 'aid';

	case MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER->value;

	case VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	case SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	case MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

}
