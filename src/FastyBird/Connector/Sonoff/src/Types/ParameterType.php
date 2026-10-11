<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Device parameters types
 */
enum ParameterType: string
{

	case DEVICE = 'device';

	case CHANNEL = 'channel';

}
