<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Types;

/**
 * Device protocol version types
 */
enum DeviceProtocolVersion: string
{

	case V31 = '3.1';

	case V32 = '3.2';

	case V32_PLUS = '3.2+';

	case V33 = '3.3';

	case V34 = '3.4';

}
