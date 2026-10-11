<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar endpoint types
 */
enum OpenPulsarEndpoint: string
{

	case CHINA = 'wss://mqe.tuyacn.com:8285/';

	case AMERICA = 'wss://mqe.tuyaus.com:8285/';

	case EUROPE = 'wss://mqe.tuyaeu.com:8285/';

	case INDIA = 'wss://mqe.tuyain.com:8285/';

}
