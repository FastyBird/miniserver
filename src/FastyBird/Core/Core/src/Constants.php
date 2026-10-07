<?php declare(strict_types = 1);

namespace FastyBird\Core;

/**
 * Application constants
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
