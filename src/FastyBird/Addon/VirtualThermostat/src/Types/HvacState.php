<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * HVAC state types
 */
enum HvacState: string
{

	case OFF = 'off';

	case COOLING = 'cooling';

	case HEATING = 'heating';

}
