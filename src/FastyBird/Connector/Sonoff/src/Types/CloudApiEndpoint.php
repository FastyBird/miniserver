<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * CoolKit api endpoint types
 */
enum CloudApiEndpoint: string
{

	case CHINA = 'https://cn-apia.coolkit.cn';

	case AMERICA = 'https://us-apia.coolkit.cc';

	case EUROPE = 'https://eu-apia.coolkit.cc';

	case ASIA = 'https://as-apia.coolkit.cc';

}
