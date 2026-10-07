<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector;

use FastyBird\Connector\Viera\Constants as VieraConstants;

/**
 * Bridge constants
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const string BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX = 'viera-connector-homekit-connector-bridge';

	public const MANUFACTURER = 'FastyBird & Panasonic';

	public const MODEL = 'Panasonic TV';

	public const ROUTE_NAME_BRIDGES = 'bridges';

	public const ROUTE_NAME_BRIDGE = 'bridge';

	public const DEFAULT_ACTIVE_IDENTIFIER = VieraConstants::TV_IDENTIFIER; // TV tuner

	public const DEFAULT_ACTIVE_CODE = VieraConstants::TV_CODE; // TV tuner

	public const RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const INPUT_SOURCE_TYPE_TV = 2;

	public const INPUT_SOURCE_TYPE_HDMI = 3;

	public const INPUT_SOURCE_TYPE_APPLICATION = 10;

}
