<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           13.09.22
 */

namespace FastyBird\Connector\HomeKit;

/**
 * Connector constants
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const string PACKAGE_NAME = 'fastybird/homekit-connector';

	public const string DEFAULT_MANUFACTURER = 'FastyBird';

	public const string DEFAULT_BRIDGE_MODEL = 'Virtual bridge';

	public const string DEFAULT_DEVICE_MODEL = 'Virtual device';

	public const string BASE_UUID = '-0000-1000-8000-0026BB765291';

	public const string RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const int DEFAULT_CONFIG_VERSION = 1;

	public const int DEFAULT_PORT = 51_827;

	public const int MAX_CONFIG_VERSION = 65_535;

	public const int STANDALONE_AID = 1; // Standalone accessory ID (i.e. not bridged)

	public const string HAP_PROTOCOL_VERSION = '01.01.00';

	public const string HAP_PROTOCOL_SHORT_VERSION = '1.1';

	public const string VERSION_REGEXP = '/^(?:[0-9]*).(?:[0-9]*).(?:[0-9]*)$/';

	public const string SERVICE_IDENTIFIER = '/^(?P<type>[a-z_]+)(?:_(?P<cnt>[0-9]+){1})$/';

}
