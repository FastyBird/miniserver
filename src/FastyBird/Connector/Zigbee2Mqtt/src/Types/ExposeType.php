<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Expose data types
 */
enum ExposeType: string
{

	case BINARY = 'binary';

	case NUMERIC = 'numeric';

	case ENUM = 'enum';

	case TEXT = 'text';

	case COMPOSITE = 'composite';

	case LIST = 'list';

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case FAN = 'fan';

	case COVER = 'cover';

	case LOCK = 'lock';

	case CLIMATE = 'climate';

}
