<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property identifier types
 */
enum ConnectorPropertyIdentifier: string
{

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case PIN_CODE = 'pin_code';

	case MAC_ADDRESS = 'mac_address';

	case SETUP_ID = 'setup_id';

	case CONFIG_VERSION = 'configuration_version';

	case PAIRED = 'paired';

	case SERVER_SECRET = 'server_secret';

	case CLIENT_PUBLIC_KEY = 'client_public_key';

	case SHARED_KEY = 'shared_key';

	case HASHING_KEY = 'hashing_key';

	case XHM_URI = 'xhm_uri';

}
