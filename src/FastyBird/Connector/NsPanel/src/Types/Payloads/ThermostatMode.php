<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Thermostat mode payload types
 */
enum ThermostatMode: string implements Payload
{

	case MANUAL = 'MANUAL';

	case AUTO = 'AUTO';

	case ECO = 'ECO';

}
