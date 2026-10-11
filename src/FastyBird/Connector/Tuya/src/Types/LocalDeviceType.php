<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Local device types
 */
enum LocalDeviceType: string
{

	case DEFAULT = 'default';

	/**
	 * Special Case Device with 22 character ID - Some of these devices
	 * Require the 0d command as the DP_QUERY status request and the list of
	 * dps requested payload
	 */
	case DEVICE_22 = 'device22';

	case DEVICE_V34 = 'device_v3.4';

	case ZIGBEE = 'zigbee';

	case GATEWAY = 'gateway';

}
