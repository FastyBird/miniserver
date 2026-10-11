<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Motor control payload types
 */
enum MotorControl: string implements Payload
{

	case OPEN = 'open';

	case CLOSE = 'close';

	case STOP = 'stop';

	case LOCK = 'lock';

}
