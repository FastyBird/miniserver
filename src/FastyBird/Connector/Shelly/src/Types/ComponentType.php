<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 devices component types
 */
enum ComponentType: string
{

	case SWITCH = 'switch';

	case COVER = 'cover';

	case LIGHT = 'light';

	case INPUT = 'input';

	case TEMPERATURE = 'temperature';

	case HUMIDITY = 'humidity';

	case VOLTMETER = 'voltmeter';

	case SCRIPT = 'script';

	case DEVICE_POWER = 'devicepower';

	case SMOKE = 'smoke';

	case ETHERNET = 'ethernet';

	case WIFI = 'wifi';

}
