<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Cloud region
 */
enum Region: string
{

	case CHINA = 'cn';

	case ASIA = 'as';

	case AMERICA = 'us';

	case EUROPE = 'eu';

}
