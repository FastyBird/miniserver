<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Presets types
 */
enum Preset: string
{

	case AUTO = 'auto';

	case MANUAL = 'manual';

	case AWAY = 'away';

	case ECO = 'eco';

	case HOME = 'home';

	case COMFORT = 'comfort';

	case SLEEP = 'sleep';

	case ANTI_FREEZE = 'anti_freeze';

}
