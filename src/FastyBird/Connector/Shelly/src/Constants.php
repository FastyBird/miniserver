<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly;

/**
 * Connector constants
 */
final class Constants
{

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const string VALUE_NOT_AVAILABLE = 'n/a';

	public const float WRITE_DEBOUNCE_DELAY = 2_000.0;

	public const string COAP_ADDRESS = '224.0.1.187';

	public const int COAP_PORT = 5_683;

	public const float STATE_READING_DELAY = 300.0;

	public const string EVENT_ERROR = 'error';

	public const string EVENT_CLOSED = 'closed';

	public const string EVENT_MESSAGE = 'message';

	public const string EVENT_CONNECTED = 'connected';

	public const string EVENT_DISCONNECTED = 'disconnected';

	public const string EVENT_LOST = 'lost';

}
