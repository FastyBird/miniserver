<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

use FastyBird\Core\Constants;

/**
 * Bridges sources types
 */
enum Addon: string implements Source
{

	case NOT_SPECIFIED = Constants::NOT_SPECIFIED_SOURCE;

	case VIRTUAL_THERMOSTAT = Constants::ADDON_VIRTUAL_THERMOSTAT_SOURCE;

}
