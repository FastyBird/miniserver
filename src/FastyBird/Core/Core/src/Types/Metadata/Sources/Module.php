<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants as Metadata;

/**
 * Modules sources types
 */
enum Module: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case ACCOUNTS = Metadata\Constants::MODULE_ACCOUNTS_SOURCE;

	case DEVICES = Metadata\Constants::MODULE_DEVICES_SOURCE;

	case TRIGGERS = Metadata\Constants::MODULE_TRIGGERS_SOURCE;

	case UI = Metadata\Constants::MODULE_UI_SOURCE;

}
