<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Temperature unit types
 */
enum Unit: string
{

	case CELSIUS = '°C';

	case FAHRENHEIT = '°F';

}
