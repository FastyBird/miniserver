<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Types;

/**
 * Property parameters
 */
enum PropertyParameter: string
{

	case NAME = 'name';

	case VALUE = 'value';

	case GROUP = 'group';

}
