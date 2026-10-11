<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Button press payload types
 */
enum Press: string implements Payload
{

	case SINGLE_PRESS = 'singlePress';

	case DOUBLE_PRESS = 'doublePress';

	case LONG_PRESS = 'longPress';

}
