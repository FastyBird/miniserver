<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

use FastyBird\Module\Devices\Types as DevicesTypes;

/**
 * Connector property identifier types
 */
enum ConnectorPropertyIdentifier: string
{

	case PORT = DevicesTypes\ConnectorPropertyIdentifier::PORT->value;

	case CLIENT_MODE = 'mode';

}
