<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Startup payload types
 */
enum Startup: string implements Payload
{

	case ON = 'on';

	case OFF = 'off';

	case STAY = 'stay';

}
