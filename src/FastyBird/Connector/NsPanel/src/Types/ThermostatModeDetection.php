<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Thermostat mode detection allowed types
 */
enum ThermostatModeDetection: string
{

	case HUMIDITY = 'humidity';

	case TEMPERATURE = 'temperature';

}
