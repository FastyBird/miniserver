<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Connector mode types
 */
enum ConnectorMode: string
{

	case EXECUTE = 'execute';

	case DISCOVER = 'discover';

}
