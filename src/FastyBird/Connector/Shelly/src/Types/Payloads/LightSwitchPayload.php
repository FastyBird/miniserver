<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Light switch payload values
 */
enum LightSwitchPayload: string implements Payload
{

	case ON = 'on';

	case OFF = 'off';

	case TOGGLE = 'toggle';

}
