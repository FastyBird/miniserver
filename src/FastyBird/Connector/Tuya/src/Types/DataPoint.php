<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Data point types
 */
enum DataPoint: string
{

	case LOCAL = 'local';

	case CLOUD = 'cloud';

}
