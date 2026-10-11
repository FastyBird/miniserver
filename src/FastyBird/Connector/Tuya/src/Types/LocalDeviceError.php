<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Local device communication error types
 */
enum LocalDeviceError: string
{

	case PAYLOAD = 'payload';

	case DEVICE_TYPE = 'device_type';

}
