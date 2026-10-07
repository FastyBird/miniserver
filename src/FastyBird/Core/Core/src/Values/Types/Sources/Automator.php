<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Triggers automators sources types
 */
enum Automator: string implements Source
{

	case NOT_SPECIFIED = '*';

	case DEVICE_MODULE = 'com.fastybird.device-module-automator';

	case DATE_TIME = 'com.fastybird.date-time-automator';

}
