<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Device parameters groups
 */
enum ParameterGroup: string
{

	case DEVICE = 'device';

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case THERMOSTAT = 'thermostat';

	case FAN = 'fan';

	case SENSOR = 'sensor';

	case HUMIDIFIER = 'humidifier';

	case OTHER = 'other';

	case BRIDGE = 'bridge';

	case SCENE_WHITE = 'white';

	case SCENE_BRIGHT = 'bright';

	case SCENE_READ = 'read';

	case SCENE_COMPUTER = 'computer';

	case SCENE_NIGHT_LIGHT = 'nightLight';

	case SCENE_COLOR = 'color';

	case SCENE_GOOD_NIGHT = 'goodNight';

	case SCENE_PARTY = 'party';

	case SCENE_LEISURE = 'leisure';

	case SCENE_SOFT = 'soft';

	case SCENE_COLORFUL = 'colorful';

}
