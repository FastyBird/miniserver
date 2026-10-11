<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Thermostat temperature unit payload types
 */
enum TemperatureScale: string implements Payload
{

	case CELSIUS = 'c';

	case FAHRENHEIT = 'f';

}
