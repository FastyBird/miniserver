<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants as Metadata;

/**
 * Triggers automators sources types
 */
enum Automator: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case DEVICE_MODULE = Metadata\Constants::AUTOMATOR_DEVICE_MODULE_SOURCE;

	case DATE_TIME = Metadata\Constants::AUTOMATOR_DATE_TIME_SOURCE;

}
