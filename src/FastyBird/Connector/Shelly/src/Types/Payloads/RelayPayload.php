<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Relay payload values
 */
enum RelayPayload: string implements Payload
{

	case ON = 'on';

	case OFF = 'off';

	case TOGGLE = 'toggle';

}
