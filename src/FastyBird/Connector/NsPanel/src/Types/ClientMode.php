<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Connector client modes
 */
enum ClientMode: string
{

	case GATEWAY = 'gateway';

	case DEVICE = 'device';

	case BOTH = 'both';

}
