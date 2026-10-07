<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use BackedEnum;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Values\Transformers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Core\WebSockets\Subscribers;
use FastyBird\Module\Accounts;
use FastyBird\Module\Devices;
use FastyBird\Module\Triggers;
use FastyBird\Module\Ui;
use FastyBird\Plugin\RedisDb\DI as RedisDbDI;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function is_string;

/**
 * Every value of the dissolved Core `Constants` class, pinned at its census T5 destination (T12-22).
 *
 * E5.10 (#642) dissolved the class: each of its 63 constants moved to the type that owns it, or
 * became a literal in a Values\Types\Sources enum case, under rules C1 to C4 (C5's only constant,
 * TOKEN_URI_NAME, was never read and #635 deleted it). VALUES is the table #648 recorded against the
 * class itself, unedited; DESTINATIONS says where each constant lives now. A value that changed on
 * the way is a red test, not a review comment.
 *
 * It lives in the application tier because the destinations span Core, four modules, three bridges
 * and a plugin, which no single package's tests may reference (make layers).
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

	/**
	 * The old constant name => every place its value lives now: a class constant, of any
	 * visibility, or a backed enum case. NOT_SPECIFIED_SOURCE became the NOT_SPECIFIED case of all
	 * six Sources enums.
	 */
	private const array DESTINATIONS = [
		'EXCHANGE_CHANNEL_NAME' => [[RedisDbDI\RedisDbExtension::class, 'EXCHANGE_CHANNEL_NAME']],
		'VALUE_NOT_SET' => [[Utilities\Value::class, 'NOT_SET']],
		'ROUTER_API_PREFIX' => [[Routing\Router::class, 'API_PREFIX']],
		'NOT_SPECIFIED_SOURCE' => [
			[Sources\Module::class, 'NOT_SPECIFIED'],
			[Sources\Plugin::class, 'NOT_SPECIFIED'],
			[Sources\Connector::class, 'NOT_SPECIFIED'],
			[Sources\Automator::class, 'NOT_SPECIFIED'],
			[Sources\Addon::class, 'NOT_SPECIFIED'],
			[Sources\Bridge::class, 'NOT_SPECIFIED'],
		],
		'MODULE_ACCOUNTS_SOURCE' => [[Sources\Module::class, 'ACCOUNTS']],
		'MODULE_DEVICES_SOURCE' => [[Sources\Module::class, 'DEVICES']],
		'MODULE_TRIGGERS_SOURCE' => [[Sources\Module::class, 'TRIGGERS']],
		'MODULE_UI_SOURCE' => [[Sources\Module::class, 'UI']],
		'PLUGIN_COUCHDB_SOURCE' => [[Sources\Plugin::class, 'COUCHDB']],
		'PLUGIN_RABBITMQ_SOURCE' => [[Sources\Plugin::class, 'RABBITMQ']],
		'PLUGIN_REDISDB_SOURCE' => [[Sources\Plugin::class, 'REDISDB']],
		'PLUGIN_REDISDB_CACHE_SOURCE' => [[Sources\Plugin::class, 'REDISDB_CACHE']],
		'PLUGIN_WS_SERVER_SOURCE' => [[Sources\Plugin::class, 'WS_SERVER']],
		'PLUGIN_WEB_SERVER_SOURCE' => [[Sources\Plugin::class, 'WEB_SERVER']],
		'PLUGIN_API_KEY' => [[Sources\Plugin::class, 'API_KEY']],
		'CONNECTOR_FB_BUS_SOURCE' => [[Sources\Connector::class, 'FB_BUS']],
		'CONNECTOR_FB_MQTT_SOURCE' => [[Sources\Connector::class, 'FB_MQTT']],
		'CONNECTOR_SHELLY_SOURCE' => [[Sources\Connector::class, 'SHELLY']],
		'CONNECTOR_TUYA_SOURCE' => [[Sources\Connector::class, 'TUYA']],
		'CONNECTOR_SONOFF_SOURCE' => [[Sources\Connector::class, 'SONOFF']],
		'CONNECTOR_MODBUS_SOURCE' => [[Sources\Connector::class, 'MODBUS']],
		'CONNECTOR_HOMEKIT_SOURCE' => [[Sources\Connector::class, 'HOMEKIT']],
		'CONNECTOR_VIRTUAL_SOURCE' => [[Sources\Connector::class, 'VIRTUAL']],
		'CONNECTOR_TERMINAL_SOURCE' => [[Sources\Connector::class, 'TERMINAL']],
		'CONNECTOR_VIERA_SOURCE' => [[Sources\Connector::class, 'VIERA']],
		'CONNECTOR_NS_PANEL_SOURCE' => [[Sources\Connector::class, 'NS_PANEL']],
		'CONNECTOR_ZIGBEE2MQTT_SOURCE' => [[Sources\Connector::class, 'ZIGBEE2MQTT']],
		'AUTOMATOR_DEVICE_MODULE_SOURCE' => [[Sources\Automator::class, 'DEVICE_MODULE']],
		'AUTOMATOR_DATE_TIME_SOURCE' => [[Sources\Automator::class, 'DATE_TIME']],
		'ADDON_VIRTUAL_THERMOSTAT_SOURCE' => [[Sources\Addon::class, 'VIRTUAL_THERMOSTAT']],
		'BRIDGE_DEVICES_MODULE_UI_MODULE_SOURCE' => [[Sources\Bridge::class, 'DEVICES_MODULE_UI_MODULE']],
		'BRIDGE_REDISDB_PLUGIN_DEVICES_MODULE_SOURCE' => [[Sources\Bridge::class, 'REDISDB_PLUGIN_DEVICES_MODULE']],
		'BRIDGE_REDISDB_PLUGIN_TRIGGERS_MODULE_SOURCE' => [[Sources\Bridge::class, 'REDISDB_PLUGIN_TRIGGERS_MODULE']],
		'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE' => [[Sources\Bridge::class, 'SHELLY_CONNECTOR_HOMEKIT_CONNECTOR']],
		'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_SOURCE' => [[Sources\Bridge::class, 'VIERA_CONNECTOR_HOMEKIT_CONNECTOR']],
		'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_SOURCE' => [
			[Sources\Bridge::class, 'VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR'],
		],
		'MODULE_ACCOUNTS_PREFIX' => [[Accounts\Constants::class, 'MODULE_ACCOUNTS_PREFIX']],
		'MODULE_DEVICES_PREFIX' => [[Devices\Constants::class, 'MODULE_DEVICES_PREFIX']],
		'MODULE_TRIGGERS_PREFIX' => [[Triggers\Constants::class, 'MODULE_TRIGGERS_PREFIX']],
		'MODULE_UI_PREFIX' => [[Ui\Constants::class, 'MODULE_UI_PREFIX']],
		'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
			[ShellyConnectorHomeKitConnector\Constants::class, 'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'],
		],
		'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
			[VieraConnectorHomeKitConnector\Constants::class, 'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'],
		],
		'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX' => [
			[
				VirtualThermostatAddonHomeKitConnector\Constants::class,
				'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX',
			],
		],
		'MESSAGE_BUS_PREFIX_KEY' => [[Publisher\MessagePublisher::class, 'ROUTING_KEY_PREFIX']],
		'VALUE_FORMAT_NUMBER_RANGE' => [[Devices\Constants::class, 'VALUE_FORMAT_NUMBER_RANGE']],
		'VALUE_FORMAT_STRING_ENUM' => [[Devices\Constants::class, 'VALUE_FORMAT_STRING_ENUM']],
		'VALUE_FORMAT_COMBINED_ENUM' => [[Devices\Constants::class, 'VALUE_FORMAT_COMBINED_ENUM']],
		'VALUE_EQUATION_TRANSFORMER' => [[Transformers\EquationTransformer::class, 'PATTERN']],
		'PERMISSIONS_DELIMITER' => [[Access\Checker::class, 'PERMISSIONS_DELIMITER']],
		'ACCESS_TOKEN_COOKIE' => [[Identity\TokenReader::class, 'COOKIE_NAME']],
		'TOKEN_HEADER_NAME' => [[Identity\TokenReader::class, 'HEADER_NAME']],
		'TOKEN_HEADER_REGEXP' => [[Identity\TokenReader::class, 'HEADER_PATTERN']],
		'TOKEN_CLAIM_USER' => [[Identity\TokenBuilder::class, 'CLAIM_USER']],
		'TOKEN_CLAIM_ROLES' => [[Identity\TokenBuilder::class, 'CLAIM_ROLES']],
		'ROLE_ANONYMOUS' => [[Identity\User::class, 'ROLE_ANONYMOUS']],
		'ROLE_VISITOR' => [[Identity\User::class, 'ROLE_VISITOR']],
		'ROLE_USER' => [[Identity\User::class, 'ROLE_USER']],
		'ROLE_MANAGER' => [[Identity\User::class, 'ROLE_MANAGER']],
		'ROLE_ADMINISTRATOR' => [[Identity\User::class, 'ROLE_ADMINISTRATOR']],
		'USER_ANONYMOUS' => [[Identity\User::class, 'ANONYMOUS_ID']],
		'WS_HEADER_AUTHORIZATION' => [[Subscribers\Client::class, 'HEADER_AUTHORIZATION']],
		'WS_HEADER_WS_KEY' => [[Subscribers\Client::class, 'HEADER_WS_KEY']],
		'WS_HEADER_ORIGIN' => [[Subscribers\Client::class, 'HEADER_ORIGIN']],
	];

	/**
	 * @throws ReflectionException
	 */
	public function testEveryConstantHasItsPinnedValue(): void
	{
		$actual = [];

		foreach (self::DESTINATIONS as $name => $destinations) {
			$values = [];

			foreach ($destinations as [$class, $member]) {
				$values[] = self::valueAt($class, $member);
			}

			$values = array_values(array_unique($values));

			// Several destinations that disagree show up as a list instead of the pinned string.
			$actual[$name] = count($values) === 1 ? $values[0] : $values;
		}

		self::assertSame(self::VALUES, $actual);
	}

	/**
	 * Each of the 63 is read above, under its old name: testEveryConstantHasItsPinnedValue fails on
	 * any name missing from DESTINATIONS or not in VALUES. What is left to pin is the class itself.
	 */
	public function testTheConstantsClassIsGone(): void
	{
		self::assertFalse(class_exists('FastyBird\\Core\\Constants'));
	}

	/**
	 * A backed enum case is a class constant too, whose value is the case object.
	 *
	 * @param class-string $class
	 *
	 * @throws ReflectionException
	 */
	private static function valueAt(string $class, string $member): string|int|null
	{
		$reflection = new ReflectionClass($class);

		if (!$reflection->hasConstant($member)) {
			return null;
		}

		$value = $reflection->getConstant($member);

		if ($value instanceof BackedEnum) {
			return $value->value;
		}

		return is_string($value) ? $value : null;
	}

}
