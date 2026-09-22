<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Constants
 * @since          1.0.0
 *
 * @date           04.05.20
 */

namespace FastyBird\Core\Constants;

/**
 * Application constants
 *
 * @package        FastyBird:Core!
 * @subpackage     Constants
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * METADATA
	 */

	public const string EXCHANGE_CHANNEL_NAME = 'fb_exchange';

	public const string VALUE_NOT_SET = 'N/A';

	/**
	 * API ROUTING
	 */

	public const string ROUTER_API_PREFIX = 'api';

	/**
	 * EXTENSIONS SOURCES
	 */

	public const string NOT_SPECIFIED_SOURCE = '*';

	public const string MODULE_ACCOUNTS_SOURCE = 'com.fastybird.accounts-module';

	public const string MODULE_DEVICES_SOURCE = 'com.fastybird.devices-module';

	public const string MODULE_TRIGGERS_SOURCE = 'com.fastybird.triggers-module';

	public const string MODULE_UI_SOURCE = 'com.fastybird.ui-module';

	public const string PLUGIN_COUCHDB_SOURCE = 'com.fastybird.couchdb-plugin';

	public const string PLUGIN_RABBITMQ_SOURCE = 'com.fastybird.rabbitmq-plugin';

	public const string PLUGIN_REDISDB_SOURCE = 'com.fastybird.redisdb-plugin';

	public const string PLUGIN_REDISDB_CACHE_SOURCE = 'com.fastybird.redisdb-cache-plugin';

	public const string PLUGIN_WS_SERVER_SOURCE = 'com.fastybird.ws-server-plugin';

	public const string PLUGIN_WEB_SERVER_SOURCE = 'com.fastybird.web-server-plugin';

	public const string PLUGIN_API_KEY = 'com.fastybird.api-key-plugin';

	public const string CONNECTOR_FB_BUS_SOURCE = 'com.fastybird.fb-bus-connector';

	public const string CONNECTOR_FB_MQTT_SOURCE = 'com.fastybird.fb-mqtt-connector';

	public const string CONNECTOR_SHELLY_SOURCE = 'com.fastybird.shelly-connector';

	public const string CONNECTOR_TUYA_SOURCE = 'com.fastybird.tuya-connector';

	public const string CONNECTOR_SONOFF_SOURCE = 'com.fastybird.sonoff-connector';

	public const string CONNECTOR_MODBUS_SOURCE = 'com.fastybird.modbus-connector';

	public const string CONNECTOR_HOMEKIT_SOURCE = 'com.fastybird.homekit-connector';

	public const string CONNECTOR_VIRTUAL_SOURCE = 'com.fastybird.virtual-connector';

	public const string CONNECTOR_TERMINAL_SOURCE = 'com.fastybird.terminal-connector';

	public const string CONNECTOR_VIERA_SOURCE = 'com.fastybird.viera-connector';

	public const string CONNECTOR_NS_PANEL_SOURCE = 'com.fastybird.ns-panel-connector';

	public const string CONNECTOR_ZIGBEE2MQTT_SOURCE = 'com.fastybird.zigbee2mqtt-connector';

	public const string AUTOMATOR_DEVICE_MODULE_SOURCE = 'com.fastybird.device-module-automator';

	public const string AUTOMATOR_DATE_TIME_SOURCE = 'com.fastybird.date-time-automator';

	public const string ADDON_VIRTUAL_THERMOSTAT_SOURCE = 'com.fastybird.virtual-thermostat-addon';

	public const string BRIDGE_DEVICES_MODULE_UI_MODULE_SOURCE = 'com.fastybird.devices-module-ui-module-bridge';

	public const string BRIDGE_REDISDB_PLUGIN_DEVICES_MODULE_SOURCE = 'com.fastybird.redisdb-plugin-devices-module-bridge';

	public const string BRIDGE_REDISDB_PLUGIN_TRIGGERS_MODULE_SOURCE = 'com.fastybird.redisdb-plugin-triggers-module-bridge';

	public const string BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE = 'com.fastybird.shelly-connector-homekit-connector-bridge';

	public const string BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE = 'com.fastybird.viera-connector-homekit-connector-bridge';

	public const string BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_SOURCE = 'com.fastybird.virtual-thermostat-addon-homekit-connector-bridge';

	/**
	 * MODULE PREFIXES
	 */

	public const string MODULE_ACCOUNTS_PREFIX = 'accounts-module';

	public const string MODULE_DEVICES_PREFIX = 'devices-module';

	public const string MODULE_TRIGGERS_PREFIX = 'triggers-module';

	public const string MODULE_UI_PREFIX = 'ui-module';

	/**
	 * BRIDGE PREFIXES
	 */

	public const string BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX = 'shelly-connector-homekit-connector-bridge';

	public const string BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX = 'viera-connector-homekit-connector-bridge';

	public const string BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX = 'virtual-thermostat-addon-homekit-connector-bridge';

	/**
	 * MESSAGE BUS
	 */

	public const string MESSAGE_BUS_PREFIX_KEY = 'fb.exchange';

	/**
	 * VALUE FORMAT
	 */

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string VALUE_FORMAT_NUMBER_RANGE = '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f){1}\|)?(?:(?:\-)?(?:\d)*(?:.(?:\d)+)?))?(?:\:(?:(?:(?:i8|u8|i16|u16|i32|u32|f){1}\|)?(?:\d)*(?:.(?:\d)+)?)){1}$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string VALUE_FORMAT_STRING_ENUM = '/^(?:[a-zA-Z0-9+°](?:[a-zA-Z0-9-?_?:?.+\+\/°])*)(?:,(?:[a-zA-Z0-9+°](?:[a-zA-Z0-9-?_?:?.+\+\/°])*))*(?:,)?$/u';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string VALUE_FORMAT_COMBINED_ENUM = '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\|)?(?:[a-zA-Z0-9+°]-?_?\.?)*)(?:\:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\|)?(?:[a-zA-Z0-9+°]-?_?\.?)*){2}(?:,(?:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\|)?(?:[a-zA-Z0-9+°]-?_?\.?)*)(?:\:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\|)?(?:[a-zA-Z0-9+°]-?_?\.?)*){2})*$/u';

	/**
	 * VALUE TRANSFORMERS
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string VALUE_EQUATION_TRANSFORMER = '/^equation:(?:(?:x=)(?<equation_x>(?:(?:[\d.y]?)*(?:[\+\-\^\*\:\/\(\)])*(?:\s)*)*)){1}(?:\|(?:(?:y=)(?<equation_y>(?:(?:[\d.x]?)*(?:[\+\-\^\*\:\/\(\)])*(?:\s)*)*))){0,1}$/';

	/**
	 * SIMPLE AUTH -- ACL
	 */

	// Permissions string delimiter
	public const string PERMISSIONS_DELIMITER = ':';

	public const string ACCESS_TOKEN_COOKIE = 'token';

	/**
	 * SIMPLE AUTH -- Security tokens
	 */

	public const string TOKEN_URI_NAME = 'authorization';

	public const string TOKEN_HEADER_NAME = 'authorization';

	public const string TOKEN_HEADER_REGEXP = '/Bearer\s+(.*)$/i';

	public const string TOKEN_CLAIM_USER = 'user';

	public const string TOKEN_CLAIM_ROLES = 'roles';

	/**
	 * SIMPLE AUTH -- Defined roles
	 */

	// Anonymous
	public const string ROLE_ANONYMOUS = 'guest';

	// Signed in
	public const string ROLE_VISITOR = 'visitor';

	public const string ROLE_USER = 'user';

	public const string ROLE_MANAGER = 'manager';

	public const string ROLE_ADMINISTRATOR = 'administrator';

	public const string USER_ANONYMOUS = 'guest';

	/**
	 * WS SERVER -- Service headers
	 */

	public const string WS_HEADER_AUTHORIZATION = 'authorization';

	public const string WS_HEADER_WS_KEY = 'x-ws-key';

	public const string WS_HEADER_ORIGIN = 'origin';

}
