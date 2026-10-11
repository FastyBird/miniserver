<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * HVAC modes types
 */
enum HvacMode: string
{

	case OFF = 'off';

	case HEAT = 'heat';

	case COOL = 'cool';

	case AUTO = 'auto';

}
