<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Roller payload values
 */
enum RollerPayload: string implements Payload
{

	case STOP = 'stop';

	case OPEN = 'open';

	case CLOSE = 'close';

}
