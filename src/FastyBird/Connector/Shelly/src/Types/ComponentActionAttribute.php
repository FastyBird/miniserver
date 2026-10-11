<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 devices component action attribute
 */
enum ComponentActionAttribute: string
{

	case ON = 'on';

	case POSITION = 'pos';

	case BRIGHTNESS = 'brightness';

}
