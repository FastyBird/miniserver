<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Server status types
 */
enum ServerStatus: string
{

	case SUCCESS = '0';

	case ENDPOINT_UNREACHABLE = 'ENDPOINT_UNREACHABLE';

	case ENDPOINT_LOW_POWER = 'ENDPOINT_LOW_POWER';

	case INVALID_DIRECTIVE = 'INVALID_DIRECTIVE';

	case NO_SUCH_ENDPOINT = 'NO_SUCH_ENDPOINT';

	case NOT_SUPPORTED_IN_CURRENT_MODE = 'NOT_SUPPORTED_IN_CURRENT_MODE';

	case INTERNAL_ERROR = 'INTERNAL_ERROR';

}
