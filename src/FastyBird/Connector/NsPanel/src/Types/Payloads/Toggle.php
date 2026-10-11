<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Toggle payload types
 */
enum Toggle: string implements Payload
{

	case ON = 'on';

	case OFF = 'off';

	case TOGGLE = 'toggle';

}
