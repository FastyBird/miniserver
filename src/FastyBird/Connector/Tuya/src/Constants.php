<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya;

/**
 * Connector constants
 */
final class Constants
{

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const string EVENT_MESSAGE = 'message';

	public const string EVENT_ERROR = 'error';

	public const string EVENT_DISCONNECTED = 'disconnected';

	public const string EVENT_CONNECTED = 'connected';

	public const string EVENT_LOST = 'lost';

}
