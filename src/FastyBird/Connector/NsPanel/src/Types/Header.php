<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Requests & Responses header name types
 */
enum Header: string
{

	case RESPONSE = 'Response';

	case ERROR_RESPONSE = 'ErrorResponse';

	case UPDATE_DEVICE_STATES_RESPONSE = 'UpdateDeviceStatesResponse';

	case DISCOVERY_REQUEST = 'DiscoveryRequest';

	case DEVICE_STATES_CHANGE_REPORT = 'DeviceStatesChangeReport';

	case DEVICE_ONLINE_CHANGE_REPORT = 'DeviceOnlineChangeReport';

	case UPDATE_DEVICE_STATES = 'UpdateDeviceStates';

}
