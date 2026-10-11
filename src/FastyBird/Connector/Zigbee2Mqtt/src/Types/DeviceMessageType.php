<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Device message types
 */
enum DeviceMessageType: string
{

	case GET = 'get';

	case AVAILABILITY = 'availability';

}
