<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP accessory type
 */
enum AccessoryType: string
{

	case GENERIC = 'generic';

	case LIGHT_HSB = 'light_hsb';

}
