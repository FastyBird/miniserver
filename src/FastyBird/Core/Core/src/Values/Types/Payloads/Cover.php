<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Payloads;

/**
 * Cover/Roller supported payload types
 */
enum Cover: string implements Payload
{

	case OPEN = 'cover_open';

	case OPENING = 'cover_opening';

	case OPENED = 'cover_opened';

	case CLOSE = 'cover_close';

	case CLOSING = 'cover_closing';

	case CLOSED = 'cover_closed';

	case STOP = 'cover_stop';

	case STOPPED = 'cover_stopped';

	case CALIBRATE = 'cover_calibrate';

	case CALIBRATING = 'cover_calibrating';

	case LOCK = 'cover_lock';

}
