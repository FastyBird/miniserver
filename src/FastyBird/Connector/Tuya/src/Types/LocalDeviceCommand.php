<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Local device command codes
 */
enum LocalDeviceCommand: int
{

	case UDP = 0;

	case AP_CONFIG = 1;

	case ACTIVE = 2;

	case SESS_KEY_NEG_START = 3;

	case SESS_KEY_NEG_RESP = 4;

	case SESS_KEY_NEG_FINISH = 5;

	case UNBIND = 6;

	case CONTROL = 7;

	case STATUS = 8;

	case HEART_BEAT = 9;

	case DP_QUERY = 10;

	case QUERY_WIFI = 11;

	case TOKEN_BIND = 12;

	case CONTROL_NEW = 13;

	case ENABLE_WIFI = 14;

	case DP_QUERY_NEW = 16;

	case SCENE_EXECUTE = 17;

	case UPDATE_DPS = 18;

	case UDP_NEW = 19;

	case AP_CONFIG_NEW = 20;

	case LAN_GW_ACTIVE = 240;

	case LAN_SUB_DEV_REQUEST = 241;

	case LAN_DELETE_SUB_DEV = 242;

	case LAN_REPORT_SUB_DEV = 243;

	case LAN_SCENE = 244;

	case LAN_PUBLISH_CLOUD_CONFIG = 245;

	case LAN_PUBLISH_APP_CONFIG = 246;

	case LAN_EXPORT_APP_CONFIG = 247;

	case LAN_PUBLISH_SCENE_PANEL = 248;

	case LAN_REMOVE_GW = 249;

	case LAN_CHECK_GW_UPDATE = 250;

	case LAN_GW_UPDATE = 251;

	case LAN_SET_GW_CHANNEL = 252;

	case UNKNOWN = -1;

}
