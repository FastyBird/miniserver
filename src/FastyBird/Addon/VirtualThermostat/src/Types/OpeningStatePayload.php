<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Opening state types
 */
enum OpeningStatePayload: string
{

	case OPENED = 'opened';

	case CLOSED = 'closed';

}
