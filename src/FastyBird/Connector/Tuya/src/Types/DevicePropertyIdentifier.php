<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Device property identifier types
 */
enum DevicePropertyIdentifier: string
{

	case IP_ADDRESS = DevicesTypes\DevicePropertyIdentifier::IP_ADDRESS->value;

	case STATE = DevicesTypes\DevicePropertyIdentifier::STATE->value;

	case MODEL = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MODEL->value;

	case MAC_ADDRESS = DevicesTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS->value;

	case SERIAL_NUMBER = DevicesTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value;

	case PROTOCOL_VERSION = 'protocol_version';

	case LOCAL_KEY = 'local_key';

	case NODE_ID = 'node_id';

	case GATEWAY_ID = 'gateway_id';

	case CATEGORY = 'category';

	case ICON = 'icon';

	case LATITUDE = 'lat';

	case LONGITUDE = 'lon';

	case PRODUCT_ID = 'product_id';

	case PRODUCT_NAME = 'product_name';

	case ENCRYPTED = 'encrypted';

	case STATE_READING_DELAY = 'state_reading_delay';

	case HEARTBEAT_DELAY = 'heartbeat_delay';

	case READ_STATE_EXCLUDE_DPS = 'read_state_exclude_dps';

}
