<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector;

/**
 * Bridge constants
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const string BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX = 'shelly-connector-homekit-connector-bridge';

	public const string MANUFACTURER = 'FastyBird & Shelly';

	public const string MODEL = 'Shelly';

	public const string ROUTE_NAME_BRIDGES = 'bridges';

	public const string ROUTE_NAME_BRIDGE = 'bridge';

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

}
