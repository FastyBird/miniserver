<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Power state payload types
 */
enum Power: string implements Payload
{

	case ON = 'on';

	case OFF = 'off';

}
