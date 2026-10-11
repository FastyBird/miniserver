<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel;

/**
 * Connector constants
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const int DEFAULT_SERVER_PORT = 52_323;

	public const string NS_PANEL_API_VERSION_V1 = '1';

	public const string STATE_NAME_KEY = '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/';

	public const string CHANNEL_IDENTIFIER = '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/';

}
