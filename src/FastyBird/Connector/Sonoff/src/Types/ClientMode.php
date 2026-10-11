<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Connector client modes
 */
enum ClientMode: string
{

	case LAN = 'lan';

	case CLOUD = 'cloud';

	case AUTO = 'auto';

	case GATEWAY = 'gateway';

}
