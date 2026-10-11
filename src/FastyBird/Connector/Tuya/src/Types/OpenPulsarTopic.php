<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar topic types
 */
enum OpenPulsarTopic: string
{

	case PROD = 'event';

	case TEST = 'event-test';

}
