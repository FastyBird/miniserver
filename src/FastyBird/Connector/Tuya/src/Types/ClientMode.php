<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Connector client modes types
 */
enum ClientMode: string
{

	case LOCAL = 'local';

	case CLOUD = 'cloud';

}
