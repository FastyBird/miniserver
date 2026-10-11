<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Bridge events
 */
enum BridgeEvent: string
{

	case DEVICE_JOINED = 'device_joined';

	case DEVICE_INTERVIEW = 'device_interview';

	case DEVICE_LEAVE = 'device_leave';

	case DEVICE_ANNOUNCE = 'device_announce';

}
