<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Configuration cache types
 */
enum ConfigurationType: string
{

	case CONNECTORS = 'connectors';

	case CONNECTORS_PROPERTIES = 'connectors_properties';

	case CONNECTORS_CONTROLS = 'connectors_controls';

	case DEVICES = 'devices';

	case DEVICES_PROPERTIES = 'devices_properties';

	case DEVICES_CONTROLS = 'devices_controls';

	case CHANNELS = 'channels';

	case CHANNELS_PROPERTIES = 'channels_properties';

	case CHANNELS_CONTROLS = 'channels_controls';

}
