<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Bridges sources types
 */
enum Addon: string implements Source
{

	case NOT_SPECIFIED = '*';

	case VIRTUAL_THERMOSTAT = 'com.fastybird.virtual-thermostat-addon';

}
