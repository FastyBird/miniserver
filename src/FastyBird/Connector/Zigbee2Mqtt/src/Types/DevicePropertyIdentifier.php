<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property name types
 */
enum DevicePropertyIdentifier: string
{

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case BASE_TOPIC = 'base_topic';

	case MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case MANUFACTURER = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER->value;

	case VERSION = DevicesTypes\DevicePropertyIdentifier::FIRMWARE_VERSION->value;

	case COMMIT = 'commit';

	case IEEE_ADDRESS = DevicesTypes\DevicePropertyIdentifier::ADDRESS->value;

	case TYPE = 'type';

	case SUPPORTED = 'supported';

	case DISABLED = 'disabled';

	case FRIENDLY_NAME = 'friendly_name';

}
