<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

use FastyBird\Core\Constants;

/**
 * Modules sources types
 */
enum Module: string implements Source
{

	case NOT_SPECIFIED = Constants::NOT_SPECIFIED_SOURCE;

	case ACCOUNTS = Constants::MODULE_ACCOUNTS_SOURCE;

	case DEVICES = Constants::MODULE_DEVICES_SOURCE;

	case TRIGGERS = Constants::MODULE_TRIGGERS_SOURCE;

	case UI = Constants::MODULE_UI_SOURCE;

}
