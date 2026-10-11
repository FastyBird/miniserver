<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff;

/**
 * Connector constants
 */
final class Constants
{

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const string COOLKIT_APP_ID = '4s1FXKC9FaGfoqXhmXSJneb3qcm1gOak'; // CoolKit application ID extracted from ha CoolKit addon

	public const string COOLKIT_APP_SECRET = 'oKvCM06gvwkRbfetd6qWRrbC3rFrbIpV'; // CoolKit application secret extracted from ha CoolKit addon

	public const string DEFAULT_APP_ID = 'R8Oq3y0eSZSYdKccHlrQzT1ACCOUT9Gv'; // CoolKit application ID extracted from ha CoolKit addon

	public const string DEFAULT_APP_SECRET = '1ve5Qk9GXfUhKAn1svnKwpAlxXkMarru'; // CoolKit application secret extracted from ha CoolKit addon

	public const string VALUE_NOT_AVAILABLE = 'N/A';

	public const string CHANNEL_GROUP = '/^(?P<group>[a-zA-Z]+)(_(?P<outlet>[0-9]+))?$/';

	public const float STATE_READING_DELAY = 300.0;

	public const float HEARTBEAT_DELAY = 150.0;

	public const float WRITE_DEBOUNCE_DELAY = 2_000.0;

}
