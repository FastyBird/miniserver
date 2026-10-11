<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Sensor types
 */
enum SensorType: string
{

	case ALARM = 'A';

	case BATTERY_LEVEL = 'B';

	case CONCENTRATION = 'C';

	case ENERGY = 'E';

	case EVENT = 'EV';

	case EVENT_COUNTER = 'EVC';

	case HUMIDITY = 'H';

	case CURRENT = 'I';

	case LUMINOSITY = 'L';

	case POWER = 'P';

	case STATUS = 'S';

	case TEMPERATURE = 'T';

	case VOLTAGE = 'V';

}
