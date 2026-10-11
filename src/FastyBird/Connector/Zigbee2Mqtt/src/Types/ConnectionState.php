<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Connection states
 */
enum ConnectionState: string
{

	case ONLINE = 'online';

	case OFFLINE = 'offline';

	case UNKNOWN = 'unknown';

	case ALERT = 'alert';

}
