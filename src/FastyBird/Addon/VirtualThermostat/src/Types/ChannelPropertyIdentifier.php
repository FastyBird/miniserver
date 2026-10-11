<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Types;

/**
 * Channel property identifier types
 */
enum ChannelPropertyIdentifier: string
{

	// ACTORS & SENSORS
	case HEATER_ACTOR = 'heater_actor';

	case COOLER_ACTOR = 'cooler_actor';

	case ROOM_TEMPERATURE_SENSOR = 'room_temperature_sensor';

	case FLOOR_TEMPERATURE_SENSOR = 'floor_temperature_sensor';

	case ROOM_HUMIDITY_SENSOR = 'room_humidity_sensor';

	case OPENING_SENSOR = 'opening_sensor';

	// CONFIGURATION
	case MAXIMUM_FLOOR_TEMPERATURE = 'max_floor_temperature';

	case LOW_TARGET_TEMPERATURE_TOLERANCE = 'low_target_temperature_tolerance';

	case HIGH_TARGET_TEMPERATURE_TOLERANCE = 'high_target_temperature_tolerance';

	case MINIMUM_CYCLE_DURATION = 'min_cycle_duration';

	case UNIT = 'unit';

	// PRESET
	case TARGET_ROOM_TEMPERATURE = 'target_room_temperature';

	case TARGET_ROOM_HUMIDITY = 'target_room_humidity';

	case COOLING_THRESHOLD_TEMPERATURE = 'cooling_threshold_temperature';

	case HEATING_THRESHOLD_TEMPERATURE = 'heating_threshold_temperature';

	// STATE
	case CURRENT_FLOOR_TEMPERATURE = 'current_floor_temperature';

	case CURRENT_ROOM_TEMPERATURE = 'current_room_temperature';

	case CURRENT_ROOM_HUMIDITY = 'current_room_humidity';

	case CURRENT_OPENINGS_STATE = 'current_openings_state';

	case FLOOR_OVERHEATING = 'floor_overheating';

	case PRESET_MODE = 'preset_mode';

	case HVAC_MODE = 'hvac_mode';

	case HVAC_STATE = 'hvac_state';

}
