<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * CoolKit sockets endpoint types
 */
enum CloudSocketsEndpoint: string
{

	case CHINA = 'https://cn-dispa.coolkit.cn';

	case AMERICA = 'https://us-dispa.coolkit.cc';

	case EUROPE = 'https://eu-dispa.coolkit.cc';

	case ASIA = 'https://as-dispa.coolkit.cc';

}
