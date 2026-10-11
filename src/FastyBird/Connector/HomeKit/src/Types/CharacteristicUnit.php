<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Types;

/**
 * HAP accessory permissions type
 */
enum CharacteristicUnit: string
{

	case CELSIUS = 'celsius';

	case PERCENTAGE = 'percentage';

	case ARC_DEGREES = 'arcdegrees';

	case LUX = 'lux';

	case SECONDS = 'seconds';

}
