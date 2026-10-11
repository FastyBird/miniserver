<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types;

/**
 * Input component input types
 */
enum InputType: string
{

	case SWITCH = 'switch';

	case BUTTON = 'button';

	case ANALOG = 'analog';

}
