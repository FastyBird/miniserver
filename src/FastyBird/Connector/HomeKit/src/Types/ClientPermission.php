<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP accessory permissions type
 */
enum ClientPermission: int
{

	case USER = 0;

	case ADMIN = 1;

}
