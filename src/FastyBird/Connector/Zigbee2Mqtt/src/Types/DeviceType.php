<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Device types
 */
enum DeviceType: string
{

	case END_DEVICE = 'EndDevice';

	case COORDINATOR = 'Coordinator';

	case ROUTER = 'Router';

	case UNKNOWN = 'Unknown';

}
