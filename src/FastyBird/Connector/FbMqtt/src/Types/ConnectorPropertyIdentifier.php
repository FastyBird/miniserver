<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property name types
 */
enum ConnectorPropertyIdentifier: string
{

	case SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case PROTOCOL_VERSION = 'protocol';

}
