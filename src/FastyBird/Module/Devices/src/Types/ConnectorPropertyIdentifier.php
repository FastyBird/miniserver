<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Connector property identifier types
 */
enum ConnectorPropertyIdentifier: string
{

	case STATE = 'state';

	case SERVER = 'server';

	case PORT = 'port';

	case SECURED_PORT = 'secured_port';

	case BAUD_RATE = 'baud_rate';

	case INTERFACE = 'interface';

	case ADDRESS = 'address';

}
