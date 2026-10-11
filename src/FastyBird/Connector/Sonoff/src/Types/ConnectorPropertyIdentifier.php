<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Connector property identifiers
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case REGION = 'region';

	case APP_ID = 'app_id';

	case APP_SECRET = 'app_secret';

	case GATEWAY_ID = 'gateway_id';

	case GATEWAY_API_KEY = 'gateway_api_key';

}
