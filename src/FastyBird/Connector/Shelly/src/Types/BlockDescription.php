<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Block descriptions
 */
enum BlockDescription: string
{

	case RELAY = 'relay';

	case ROLLER = 'roller';

	case LIGHT = 'light';

	case EMETER = 'emeter';

	case DEVICE = 'device';

}
