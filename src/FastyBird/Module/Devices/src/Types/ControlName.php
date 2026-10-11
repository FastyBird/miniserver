<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Control name types
 */
enum ControlName: string
{

	case CONFIGURE = 'configure';

	case RESET = 'reset';

	case FACTORY_RESET = 'factory_reset';

	case REBOOT = 'reboot';

	case TRIGGER = 'trigger';

	case DISCOVER = 'discover';

}
