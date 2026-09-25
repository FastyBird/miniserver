<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

use FastyBird\Core\Constants;

/**
 * Triggers automators sources types
 */
enum Automator: string implements Source
{

	case NOT_SPECIFIED = Constants::NOT_SPECIFIED_SOURCE;

	case DEVICE_MODULE = Constants::AUTOMATOR_DEVICE_MODULE_SOURCE;

	case DATE_TIME = Constants::AUTOMATOR_DATE_TIME_SOURCE;

}
