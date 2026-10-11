<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

/**
 * Connection state types
 */
enum ConnectionState: string
{

	case CONNECTED = 'connected';

	case DISCONNECTED = 'disconnected';

	case INIT = 'init';

	case READY = 'ready';

	case RUNNING = 'running';

	case SLEEPING = 'sleeping';

	case STOPPED = 'stopped';

	case LOST = 'lost';

	case ALERT = 'alert';

	case UNKNOWN = 'unknown';

}
