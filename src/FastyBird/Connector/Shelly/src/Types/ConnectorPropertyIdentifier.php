<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Connector property identifiers
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case CLOUD_AUTH_KEY = 'cloud_auth_key';

	case CLOUD_SERVER = 'cloud_server';

}
