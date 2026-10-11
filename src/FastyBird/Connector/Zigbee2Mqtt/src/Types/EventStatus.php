<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Event statuses
 */
enum EventStatus: string
{

	case STARTED = 'started';

	case SUCCESSFUL = 'successful';

	case FAILED = 'failed';

}
