<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Connector client modes
 */
enum ClientMode: string
{

	case LOCAL = 'local';

	case CLOUD = 'cloud';

	case INTEGRATOR = 'integrator';

	case MQTT = 'mqtt';

}
