<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 devices component attribute types
 */
enum ComponentAttributeType: string
{

	case OUTPUT = 'output';

	case HUMIDITY = 'humidity';

	case ACTIVE_POWER = 'active_power';

	case POWER_FACTOR = 'power_factor';

	case ACTIVE_ENERGY_TOTAL = 'active_energy_total';

	case CURRENT = 'current';

	case VOLTAGE = 'voltage';

	case TEMPERATURE_CELSIUS = 'temperature_celsius';

	case TEMPERATURE_FAHRENHEIT = 'temperature_fahrenheit';

	case STATE = 'state';

	case CURRENT_POSITION = 'current_position';

	case TARGET_POSITION = 'target_position';

	case BRIGHTNESS = 'brightness';

	case SWITCH = 'switch';

	case BUTTON = 'button';

	case ANALOG = 'analog';

	case CELSIUS = 'celsius';

	case FAHRENHEIT = 'fahrenheit';

	case RELATIVE_HUMIDITY = 'relative_humidity';

	case X_VOLTAGE = 'xvoltage';

	case RUNNING = 'running';

	case BATTERY_VOLTAGE = 'battery_voltage';

	case BATTER_PERCENT = 'battery_percent';

	case EXTERNAL_PRESENT = 'external_present';

	case ALARM = 'alarm';

	case MUTE = 'mute';

	case RESULT = 'result';

}
