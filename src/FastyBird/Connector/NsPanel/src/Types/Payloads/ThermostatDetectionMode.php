<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Thermostat detection mode payload types
 */
enum ThermostatDetectionMode: string implements Payload
{

	case COMFORT = 'COMFORT';

	case COLD = 'COLD';

	case HOT = 'HOT';

	case DRY = 'DRY';

	case WET = 'WET';

}
