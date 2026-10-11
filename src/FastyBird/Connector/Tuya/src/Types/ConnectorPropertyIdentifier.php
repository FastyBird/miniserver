<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Connector property identifier types
 */
enum ConnectorPropertyIdentifier: string
{

	case CLIENT_MODE = 'mode';

	case ACCESS_ID = 'access_id';

	case ACCESS_SECRET = 'access_secret';

	case UID = 'uid';

	case OPENAPI_ENDPOINT = 'openapi_endpoint';

	case OPENPULSAR_ENDPOINT = 'openpulsar_endpoint';

	case OPENPULSAR_TOPIC = 'openpulsar_topic';

}
