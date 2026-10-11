<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Types\Payloads;

/**
 * Cover component payload values
 */
enum CoverPayload: string implements Payload
{

	case OPEN = 'open';

	case CLOSED = 'closed';

	case OPENING = 'opening';

	case CLOSING = 'closing';

	case STOPPED = 'stopped';

	case CALIBRATING = 'calibrating';

}
