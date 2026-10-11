<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Types;

/**
 * Device mapping group type
 */
enum DeviceType: string
{

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case ROLLER = 'roller';

	case INPUT = 'input';

}
