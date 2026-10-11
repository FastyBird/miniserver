<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Thermostat adaptive recovery status payload types
 */
enum ThermostatAdaptiveRecoveryStatus: string implements Payload
{

	case HEATING = 'HEATING';

	case INACTIVE = 'INACTIVE';

}
