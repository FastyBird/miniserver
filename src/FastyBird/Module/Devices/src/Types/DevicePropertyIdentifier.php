<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Device property identifier types
 */
enum DevicePropertyIdentifier: string
{

	case STATE = 'state';

	case BATTERY = 'battery';

	case WIFI = 'wifi';

	case SIGNAL = 'signal';

	case RSSI = 'rssi';

	case SSID = 'ssid';

	case VCC = 'vcc';

	case CPU_LOAD = 'cpu_load';

	case UPTIME = 'uptime';

	case ADDRESS = 'address';

	case IP_ADDRESS = 'ip_address';

	case DOMAIN = 'domain';

	case STATUS_LED = 'status_led';

	case FREE_HEAP = 'free_heap';

	case HARDWARE_MANUFACTURER = 'hardware_manufacturer';

	case HARDWARE_MODEL = 'hardware_model';

	case HARDWARE_VERSION = 'hardware_version';

	case HARDWARE_MAC_ADDRESS = 'hardware_mac_address';

	case FIRMWARE_MANUFACTURER = 'firmware_manufacturer';

	case FIRMWARE_NAME = 'firmware_name';

	case FIRMWARE_VERSION = 'firmware_version';

	case SERIAL_NUMBER = 'serial_number';

	case STATE_READING_DELAY = 'state_reading_delay';

	case STATE_PROCESSING_DELAY = 'state_processing_delay';

}
