<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property name types
 */
enum ConnectorPropertyIdentifier: string
{

	case STATE = DevicesTypes\ConnectorPropertyIdentifier::STATE->value;

	case SERVER = DevicesTypes\ConnectorPropertyIdentifier::SERVER->value;

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case SECURED_PORT = DevicesTypes\ConnectorPropertyIdentifier::SECURED_PORT->value;

	case CLIENT_MODE = 'mode';

	case USERNAME = 'username';

	case PASSWORD = 'password';

	case BASE_TOPIC = 'base_topic';

}
