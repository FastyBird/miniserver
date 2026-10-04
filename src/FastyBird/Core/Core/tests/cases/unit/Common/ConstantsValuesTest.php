<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Common;

use FastyBird\Core\Constants;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Every value of FastyBird\Core\Constants, pinned (census T5, T12-22).
 *
 * E5.10 (#642) dissolves the class: each constant moves to the type that owns it, or becomes a
 * literal in an enum case, under rules C1 to C5. Values never change. #642 repoints this test at
 * the destinations, constant by constant, without editing a single value below -- so a value that
 * changes on the way is a red test, not a review comment.
 */
final class ConstantsValuesTest extends TestCase
{

	private const array VALUES = [
		'EXCHANGE_CHANNEL_NAME' => 'fb_exchange',
		'VALUE_NOT_SET' => 'N/A',
		'ROUTER_API_PREFIX' => 'api',
		'NOT_SPECIFIED_SOURCE' => '*',
		'MODULE_ACCOUNTS_SOURCE' => 'com.fastybird.accounts-module',
		'MODULE_DEVICES_SOURCE' => 'com.fastybird.devices-module',
		'MODULE_TRIGGERS_SOURCE' => 'com.fastybird.triggers-module',
		'MODULE_UI_SOURCE' => 'com.fastybird.ui-module',
		'PLUGIN_COUCHDB_SOURCE' => 'com.fastybird.couchdb-plugin',
		'PLUGIN_RABBITMQ_SOURCE' => 'com.fastybird.rabbitmq-plugin',
		'PLUGIN_REDISDB_SOURCE' => 'com.fastybird.redisdb-plugin',
		'PLUGIN_REDISDB_CACHE_SOURCE' => 'com.fastybird.redisdb-cache-plugin',
		'PLUGIN_WS_SERVER_SOURCE' => 'com.fastybird.ws-server-plugin',
		'PLUGIN_WEB_SERVER_SOURCE' => 'com.fastybird.web-server-plugin',
		'PLUGIN_API_KEY' => 'com.fastybird.api-key-plugin',
		'CONNECTOR_FB_BUS_SOURCE' => 'com.fastybird.fb-bus-connector',
		'CONNECTOR_FB_MQTT_SOURCE' => 'com.fastybird.fb-mqtt-connector',
		'CONNECTOR_SHELLY_SOURCE' => 'com.fastybird.shelly-connector',
		'CONNECTOR_TUYA_SOURCE' => 'com.fastybird.tuya-connector',
		'CONNECTOR_SONOFF_SOURCE' => 'com.fastybird.sonoff-connector',
		'CONNECTOR_MODBUS_SOURCE' => 'com.fastybird.modbus-connector',
		'CONNECTOR_HOMEKIT_SOURCE' => 'com.fastybird.homekit-connector',
		'CONNECTOR_VIRTUAL_SOURCE' => 'com.fastybird.virtual-connector',
		'CONNECTOR_TERMINAL_SOURCE' => 'com.fastybird.terminal-connector',
		'CONNECTOR_VIERA_SOURCE' => 'com.fastybird.viera-connector',
		'CONNECTOR_NS_PANEL_SOURCE' => 'com.fastybird.ns-panel-connector',
		'CONNECTOR_ZIGBEE2MQTT_SOURCE' => 'com.fastybird.zigbee2mqtt-connector',
		'AUTOMATOR_DEVICE_MODULE_SOURCE' => 'com.fastybird.device-module-automator',
		'AUTOMATOR_DATE_TIME_SOURCE' => 'com.fastybird.date-time-automator',
		'ADDON_VIRTUAL_THERMOSTAT_SOURCE' => 'com.fastybird.virtual-thermostat-addon',
		'BRIDGE_DEVICES_MODULE_UI_MODULE_SOURCE' => 'com.fastybird.devices-module-ui-module-bridge',
		'BRIDGE_REDISDB_PLUGIN_DEVICES_MODULE_SOURCE' => 'com.fastybird.redisdb-plugin-devices-module-bridge',
		'BRIDGE_REDISDB_PLUGIN_TRIGGERS_MODULE_SOURCE' => 'com.fastybird.redisdb-plugin-triggers-module-bridge',
		'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE' => 'com.fastybird.shelly-connector-homekit-connector-bridge',
		'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE' => 'com.fastybird.viera-connector-homekit-connector-bridge',
		'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_SOURCE' => 'com.fastybird.virtual-thermostat-addon-homekit-connector-bridge',
		'MODULE_ACCOUNTS_PREFIX' => 'accounts-module',
		'MODULE_DEVICES_PREFIX' => 'devices-module',
		'MODULE_TRIGGERS_PREFIX' => 'triggers-module',
		'MODULE_UI_PREFIX' => 'ui-module',
		'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => 'shelly-connector-homekit-connector-bridge',
		'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => 'viera-connector-homekit-connector-bridge',
		'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX' => 'virtual-thermostat-addon-homekit-connector-bridge',
		'MESSAGE_BUS_PREFIX_KEY' => 'fb.exchange',
		'VALUE_FORMAT_NUMBER_RANGE' => '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f){1}\\|)?(?:(?:\\-)?(?:\\d)*(?:.(?:\\d)+)?))?(?:\\:(?:(?'
			. ':(?:i8|u8|i16|u16|i32|u32|f){1}\\|)?(?:\\d)*(?:.(?:\\d)+)?)){1}$/',
		'VALUE_FORMAT_STRING_ENUM' => '/^(?:[a-zA-Z0-9+°](?:[a-zA-Z0-9-?_?:?.+\\+\\/°])*)(?:,(?:[a-zA-Z0-9+°](?:[a-zA-Z0-9-?_?:?.'
			. '+\\+\\/°])*))*(?:,)?$/u',
		'VALUE_FORMAT_COMBINED_ENUM' => '/^(?:(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\\|)?(?:[a-zA-Z0-9+°]-?_?\\.?)*)(?:\\'
			. ':(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\\|)?(?:[a-zA-Z0-9+°]-?_?\\.?)*){2}(?:,(?'
			. ':(?:(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\\|)?(?:[a-zA-Z0-9+°]-?_?\\.?)*)(?:\\:(?:'
			. '(?:i8|u8|i16|u16|i32|u32|f|b|s|btn|sw|cvr){1}\\|)?(?:[a-zA-Z0-9+°]-?_?\\.?)*){2})*$/u',
		'VALUE_EQUATION_TRANSFORMER' => '/^equation:(?:(?:x=)(?<equation_x>(?:(?:[\\d.y]?)*(?:[\\+\\-\\^\\*\\:\\/\\(\\)])*(?:\\s)*)'
			. '*)){1}(?:\\|(?:(?:y=)(?<equation_y>(?:(?:[\\d.x]?)*(?:[\\+\\-\\^\\*\\:\\/\\(\\)])*(?:\\s)*'
			. ')*))){0,1}$/',
		'PERMISSIONS_DELIMITER' => ':',
		'ACCESS_TOKEN_COOKIE' => 'token',
		'TOKEN_URI_NAME' => 'authorization',
		'TOKEN_HEADER_NAME' => 'authorization',
		'TOKEN_HEADER_REGEXP' => '/Bearer\\s+(.*)$/i',
		'TOKEN_CLAIM_USER' => 'user',
		'TOKEN_CLAIM_ROLES' => 'roles',
		'ROLE_ANONYMOUS' => 'guest',
		'ROLE_VISITOR' => 'visitor',
		'ROLE_USER' => 'user',
		'ROLE_MANAGER' => 'manager',
		'ROLE_ADMINISTRATOR' => 'administrator',
		'USER_ANONYMOUS' => 'guest',
		'WS_HEADER_AUTHORIZATION' => 'authorization',
		'WS_HEADER_WS_KEY' => 'x-ws-key',
		'WS_HEADER_ORIGIN' => 'origin',
	];

	public function testEveryConstantHasItsPinnedValue(): void
	{
		self::assertSame(self::VALUES, (new ReflectionClass(Constants::class))->getConstants());
	}

	public function testThereAreSixtyFourConstants(): void
	{
		self::assertCount(64, (new ReflectionClass(Constants::class))->getConstants());
	}

}
