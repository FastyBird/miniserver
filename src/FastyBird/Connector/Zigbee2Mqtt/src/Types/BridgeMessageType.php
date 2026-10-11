<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Bridge message types
 */
enum BridgeMessageType: string
{

	case INFO = 'info';

	case STATE = 'state';

	case LOGGING = 'logging';

	case DEFINITIONS = 'definitions';

	case DEVICES = 'devices';

	case GROUPS = 'groups';

	case EVENT = 'event';

	case EXTENSIONS = 'extensions';

}
