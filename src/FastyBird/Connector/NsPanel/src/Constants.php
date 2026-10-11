<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel;

/**
 * Connector constants
 */
final class Constants
{

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const int DEFAULT_SERVER_PORT = 52_323;

	public const string NS_PANEL_API_VERSION_V1 = '1';

	public const string STATE_NAME_KEY = '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/';

	public const string CHANNEL_IDENTIFIER = '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/';

}
