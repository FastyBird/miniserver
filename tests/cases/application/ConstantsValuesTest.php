<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use BackedEnum;
use FastyBird\Addon\VirtualThermostat;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\Modbus;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Zigbee2Mqtt;
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
use function array_keys;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function dirname;
use function is_array;
use function is_file;
use function is_float;
use function is_int;
use function is_string;
use function realpath;
use function str_starts_with;
use function strlen;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Every value of the dissolved Core `Constants` class, pinned at its census T5 destination (T12-22).
 *
 * E5.10 (#642) dissolved the class: each of its 63 constants moved to the type that owns it, or
 * became a literal in a Values\Types\Sources enum case, under rules C1 to C4 (C5's only constant,
 * TOKEN_URI_NAME, was never read and #635 deleted it). Two of the 63, PLUGIN_WS_SERVER_SOURCE and
 * PLUGIN_WEB_SERVER_SOURCE, are gone from both tables since #744: they were log labels only, and
 * Core's logs now use Sources\Core::CORE with a `capability` context key. A third, PLUGIN_API_KEY,
 * went with the ApiKey plugin it labelled (#730), whose keys Core's Security capability replaced.
 * VALUES is the table #648 recorded against the class itself, unedited otherwise; DESTINATIONS says
 * where each constant lives now. A value that changed on the way is a red test, not a review
 * comment.
 *
 * #726 part 2 dissolves the packages' own `Constants` classes the same way, one package at a time:
 * route names move to the package's Router\ApiRoutes, exchange routing keys to its Types\RoutingKey
 * enum, its prefix to its DI extension, and any other constant to the class that reads it.
 * PACKAGE_VALUES pins each of those values as it was before the move, and PACKAGE_DESTINATIONS says
 * where it lives now.
 *
 * It lives in the application tier because the destinations span Core, four modules, three bridges,
 * a plugin, ten connectors and an addon, which no single package's tests may reference (make
 * layers).
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
	 * seven Sources enums.
	 */
	private const array DESTINATIONS = [
		'EXCHANGE_CHANNEL_NAME' => [[RedisDbDI\RedisDbExtension::class, 'EXCHANGE_CHANNEL_NAME']],
		'VALUE_NOT_SET' => [[Utilities\Value::class, 'NOT_SET']],
		'ROUTER_API_PREFIX' => [[Routing\Router::class, 'API_PREFIX']],
		'NOT_SPECIFIED_SOURCE' => [
			[Sources\Module::class, 'NOT_SPECIFIED'],
			[Sources\Plugin::class, 'NOT_SPECIFIED'],
			[Sources\Core::class, 'NOT_SPECIFIED'],
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
		'MODULE_DEVICES_PREFIX' => [[Devices\DI\DevicesExtension::class, 'MODULE_DEVICES_PREFIX']],
		'MODULE_TRIGGERS_PREFIX' => [[Triggers\Constants::class, 'MODULE_TRIGGERS_PREFIX']],
		'MODULE_UI_PREFIX' => [[Ui\Constants::class, 'MODULE_UI_PREFIX']],
		'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
			[
				ShellyConnectorHomeKitConnector\DI\ShellyConnectorHomeKitConnectorExtension::class,
				'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX',
			],
		],
		'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
			[
				VieraConnectorHomeKitConnector\DI\VieraConnectorHomeKitConnectorExtension::class,
				'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX',
			],
		],
		'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX' => [
			[
				VirtualThermostatAddonHomeKitConnector\DI\VirtualThermostatAddonHomeKitConnectorExtension::class,
				'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX',
			],
		],
		'MESSAGE_BUS_PREFIX_KEY' => [[Publisher\MessagePublisher::class, 'ROUTING_KEY_PREFIX']],
		'VALUE_FORMAT_NUMBER_RANGE' => [[Devices\Entities\Property::class, 'VALUE_FORMAT_NUMBER_RANGE']],
		'VALUE_FORMAT_STRING_ENUM' => [[Devices\Entities\Property::class, 'VALUE_FORMAT_STRING_ENUM']],
		'VALUE_FORMAT_COMBINED_ENUM' => [[Devices\Entities\Property::class, 'VALUE_FORMAT_COMBINED_ENUM']],
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
	 * Every value of a package's dissolved `Constants` class (#726 part 2), keyed by the class's
	 * old name and then by the constant's old name, as it was on the base of the change that
	 * dissolved it. A value Core's table above already pins is read from there.
	 *
	 * A RESOURCES_FOLDER was `__DIR__ . '/../resources'` in the Constants class, so its text named
	 * the directory of the file declaring it. It is pinned as the directory it resolves to,
	 * relative to the owner's package: `'resources'`.
	 */
	private const array PACKAGE_VALUES = [
		'FastyBird\\Module\\Devices\\Constants' => [
			'MODULE_DEVICES_PREFIX' => 'devices-module',
			'ROUTE_NAME_DEVICES' => 'devices',
			'ROUTE_NAME_DEVICE' => 'device',
			'ROUTE_NAME_DEVICE_RELATIONSHIP' => 'device.relationship',
			'ROUTE_NAME_DEVICE_PARENTS' => 'device.parents',
			'ROUTE_NAME_DEVICE_CHILDREN' => 'device.children',
			'ROUTE_NAME_DEVICE_PROPERTIES' => 'device.properties',
			'ROUTE_NAME_DEVICE_PROPERTY' => 'device.property',
			'ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP' => 'device.property.relationship',
			'ROUTE_NAME_DEVICE_PROPERTY_CHILDREN' => 'device.property.children',
			'ROUTE_NAME_DEVICE_PROPERTY_STATE' => 'device.property.state',
			'ROUTE_NAME_DEVICE_CONTROLS' => 'device.controls',
			'ROUTE_NAME_DEVICE_CONTROL' => 'device.control',
			'ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP' => 'device.control.relationship',
			'ROUTE_NAME_DEVICE_CHANNELS' => 'device.channels',
			'ROUTE_NAME_DEVICE_CHANNEL' => 'device.channel',
			'ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP' => 'device.channel.relationship',
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES' => 'device.channel.properties',
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY' => 'device.channel.property',
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP' => 'device.channel.property.relationship',
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN' => 'device.channel.property.children',
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE' => 'device.channel.property.state',
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROLS' => 'device.channel.controls',
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROL' => 'device.channel.control',
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP' => 'device.channel.control.relationship',
			'ROUTE_NAME_CHANNELS' => 'channels',
			'ROUTE_NAME_CHANNEL' => 'channel',
			'ROUTE_NAME_CHANNEL_RELATIONSHIP' => 'channel.relationship',
			'ROUTE_NAME_CHANNEL_PROPERTIES' => 'channel.properties',
			'ROUTE_NAME_CHANNEL_PROPERTY' => 'channel.property',
			'ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP' => 'channel.property.relationship',
			'ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN' => 'channel.property.children',
			'ROUTE_NAME_CHANNEL_PROPERTY_STATE' => 'channel.property.state',
			'ROUTE_NAME_CHANNEL_CONTROLS' => 'channel.controls',
			'ROUTE_NAME_CHANNEL_CONTROL' => 'channel.control',
			'ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP' => 'channel.control.relationship',
			'ROUTE_NAME_CONNECTORS' => 'connectors',
			'ROUTE_NAME_CONNECTOR' => 'connector',
			'ROUTE_NAME_CONNECTOR_RELATIONSHIP' => 'connector.relationship',
			'ROUTE_NAME_CONNECTOR_PROPERTIES' => 'connector.properties',
			'ROUTE_NAME_CONNECTOR_PROPERTY' => 'connector.property',
			'ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP' => 'connector.property.relationship',
			'ROUTE_NAME_CONNECTOR_PROPERTY_STATE' => 'connector.property.state',
			'ROUTE_NAME_CONNECTOR_CONTROLS' => 'connector.controls',
			'ROUTE_NAME_CONNECTOR_CONTROL' => 'connector.control',
			'ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP' => 'connector.control.relationship',
			'ROUTE_NAME_CONNECTOR_DEVICES' => 'connector.devices',
			'ROUTE_NAME_CONNECTOR_DEVICE' => 'connector.device',
			'ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP' => 'connector.device.relationship',
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES' => 'connector.device.properties',
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY' => 'connector.device.property',
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP' => 'connector.device.property.relationship',
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN' => 'connector.device.property.children',
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE' => 'connector.device.property.state',
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS' => 'connector.device.controls',
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL' => 'connector.device.control',
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP' => 'connector.device.control.relationship',
			'ROUTING_PREFIX' => 'fb.exchange.module.document',
			'MESSAGE_BUS_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.',
			'MESSAGE_BUS_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.',
			'MESSAGE_BUS_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.',
			'MESSAGE_BUS_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.',
			'MESSAGE_BUS_DEVICE_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.device',
			'MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.device',
			'MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.device',
			'MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.device',
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.device.property',
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.device.property',
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.device.property',
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.device.property',
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.device.property.state',
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.device.property.state',
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.device.property.state',
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.device.property.state',
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.device.control',
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.device.control',
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.device.control',
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.device.control',
			'MESSAGE_BUS_CHANNEL_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.channel',
			'MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.channel',
			'MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.channel',
			'MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.channel',
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.channel.property',
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.channel.property',
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.channel.property',
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.channel.property',
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.channel.property.state',
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.channel.property.state',
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.channel.property.state',
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.channel.property.state',
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.channel.control',
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.channel.control',
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.channel.control',
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.channel.control',
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.connector',
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.connector',
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.connector',
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.connector',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.connector.property',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.connector.property',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.connector.property',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.connector.property',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.connector.property.state',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.connector.property.state',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.connector.property.state',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.connector.property.state',
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => 'fb.exchange.module.document.reported.connector.control',
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => 'fb.exchange.module.document.created.connector.control',
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => 'fb.exchange.module.document.updated.connector.control',
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => 'fb.exchange.module.document.deleted.connector.control',
			'MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY' => 'fb.exchange.action.connector.control',
			'MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY' => 'fb.exchange.action.connector.property',
			'MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY' => 'fb.exchange.action.device.control',
			'MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY' => 'fb.exchange.action.device.property',
			'MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY' => 'fb.exchange.action.channel.control',
			'MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY' => 'fb.exchange.action.channel.property',
			'VALUE_FORMAT_NUMBER_RANGE' => self::VALUES['VALUE_FORMAT_NUMBER_RANGE'],
			'VALUE_FORMAT_STRING_ENUM' => self::VALUES['VALUE_FORMAT_STRING_ENUM'],
			'VALUE_FORMAT_COMBINED_ENUM' => self::VALUES['VALUE_FORMAT_COMBINED_ENUM'],
			'MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Entities\Connectors\Connector::class => 'fb.exchange.module.document.created.connector',
				Devices\Entities\Connectors\Properties\Property::class => 'fb.exchange.module.document.created.connector.property',
				Devices\Entities\Connectors\Controls\Control::class => 'fb.exchange.module.document.created.connector.control',
				Devices\Entities\Devices\Device::class => 'fb.exchange.module.document.created.device',
				Devices\Entities\Devices\Properties\Property::class => 'fb.exchange.module.document.created.device.property',
				Devices\Entities\Devices\Controls\Control::class => 'fb.exchange.module.document.created.device.control',
				Devices\Entities\Channels\Channel::class => 'fb.exchange.module.document.created.channel',
				Devices\Entities\Channels\Properties\Property::class => 'fb.exchange.module.document.created.channel.property',
				Devices\Entities\Channels\Controls\Control::class => 'fb.exchange.module.document.created.channel.control',
			],
			'MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Entities\Connectors\Connector::class => 'fb.exchange.module.document.updated.connector',
				Devices\Entities\Connectors\Properties\Property::class => 'fb.exchange.module.document.updated.connector.property',
				Devices\Entities\Connectors\Controls\Control::class => 'fb.exchange.module.document.updated.connector.control',
				Devices\Entities\Devices\Device::class => 'fb.exchange.module.document.updated.device',
				Devices\Entities\Devices\Properties\Property::class => 'fb.exchange.module.document.updated.device.property',
				Devices\Entities\Devices\Controls\Control::class => 'fb.exchange.module.document.updated.device.control',
				Devices\Entities\Channels\Channel::class => 'fb.exchange.module.document.updated.channel',
				Devices\Entities\Channels\Properties\Property::class => 'fb.exchange.module.document.updated.channel.property',
				Devices\Entities\Channels\Controls\Control::class => 'fb.exchange.module.document.updated.channel.control',
			],
			'MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Entities\Connectors\Connector::class => 'fb.exchange.module.document.deleted.connector',
				Devices\Entities\Connectors\Properties\Property::class => 'fb.exchange.module.document.deleted.connector.property',
				Devices\Entities\Connectors\Controls\Control::class => 'fb.exchange.module.document.deleted.connector.control',
				Devices\Entities\Devices\Device::class => 'fb.exchange.module.document.deleted.device',
				Devices\Entities\Devices\Properties\Property::class => 'fb.exchange.module.document.deleted.device.property',
				Devices\Entities\Devices\Controls\Control::class => 'fb.exchange.module.document.deleted.device.control',
				Devices\Entities\Channels\Channel::class => 'fb.exchange.module.document.deleted.channel',
				Devices\Entities\Channels\Properties\Property::class => 'fb.exchange.module.document.deleted.channel.property',
				Devices\Entities\Channels\Controls\Control::class => 'fb.exchange.module.document.deleted.channel.control',
			],
		],
		'FastyBird\\Addon\\VirtualThermostat\\Constants' => [
			'MANUFACTURER' => 'FastyBird',
			'PRESET_CHANNEL_PATTERN' => '/^preset(_(?P<preset>[a-zA-Z_]+))?$/',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants' => [
			'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => self::VALUES['BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'],
			'MANUFACTURER' => 'FastyBird & Shelly',
			'MODEL' => 'Shelly',
			'ROUTE_NAME_BRIDGES' => 'bridges',
			'ROUTE_NAME_BRIDGE' => 'bridge',
			'RESOURCES_FOLDER' => 'resources',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants' => [
			'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => self::VALUES['BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'],
			'MANUFACTURER' => 'FastyBird & Panasonic',
			'MODEL' => 'Panasonic TV',
			'ROUTE_NAME_BRIDGES' => 'bridges',
			'ROUTE_NAME_BRIDGE' => 'bridge',
			'DEFAULT_ACTIVE_IDENTIFIER' => 'TV',
			'DEFAULT_ACTIVE_CODE' => 500,
			'RESOURCES_FOLDER' => 'resources',
			'INPUT_SOURCE_TYPE_TV' => 2,
			'INPUT_SOURCE_TYPE_HDMI' => 3,
			'INPUT_SOURCE_TYPE_APPLICATION' => 10,
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants' => [
			'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX' => self::VALUES['BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX'],
			'MODEL' => 'Virtual Thermostat Bridge',
			'MANUFACTURER' => 'FastyBird',
			'ROUTE_NAME_BRIDGES' => 'bridges',
			'ROUTE_NAME_BRIDGE' => 'bridge',
		],
		'FastyBird\\Connector\\FbMqtt\\Constants' => [
			'MQTT_TOPIC_DELIMITER' => '/',
			'MQTT_API_PREFIX' => '/fb',
			'MQTT_API_V1_VERSION_PREFIX' => '/v1',
			'MQTT_API_QOS_0' => 0,
			'MQTT_API_QOS_1' => 1,
			'MQTT_API_QOS_2' => 2,
			'PAYLOAD_BOOL_TRUE_VALUE' => 'true',
			'PAYLOAD_BOOL_FALSE_VALUE' => 'false',
			'VALUE_NOT_SET' => 'value_not_set',
			'DEFAULT_SERVER_ADDRESS' => '127.0.0.1',
			'DEFAULT_SERVER_PORT' => 1_883,
			'DEFAULT_SERVER_SECURED_PORT' => 8_883,
			'WRITE_DEBOUNCE_DELAY' => 2_000.0,
		],
		'FastyBird\\Connector\\HomeKit\\Constants' => [
			'PACKAGE_NAME' => 'fastybird/homekit-connector',
			'DEFAULT_MANUFACTURER' => 'FastyBird',
			'DEFAULT_BRIDGE_MODEL' => 'Virtual bridge',
			'DEFAULT_DEVICE_MODEL' => 'Virtual device',
			'BASE_UUID' => '-0000-1000-8000-0026BB765291',
			'RESOURCES_FOLDER' => 'resources',
			'DEFAULT_CONFIG_VERSION' => 1,
			'DEFAULT_PORT' => 51_827,
			'MAX_CONFIG_VERSION' => 65_535,
			'STANDALONE_AID' => 1,
			'HAP_PROTOCOL_VERSION' => '01.01.00',
			'HAP_PROTOCOL_SHORT_VERSION' => '1.1',
			'VERSION_REGEXP' => '/^(?:[0-9]*).(?:[0-9]*).(?:[0-9]*)$/',
			'SERVICE_IDENTIFIER' => '/^(?P<type>[a-z_]+)(?:_(?P<cnt>[0-9]+){1})$/',
		],
		'FastyBird\\Connector\\Modbus\\Constants' => [
			'DEFAULT_RTU_SERIAL_INTERFACE' => '/dev/ttyAMA0',
			'MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST' => 124,
			'MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST' => 2_048,
			'WRITE_DEBOUNCE_DELAY' => 2_000.0,
			'READING_DELAY' => 150.0,
			'DEFAULT_TCP_PORT' => 502,
		],
		'FastyBird\\Connector\\NsPanel\\Constants' => [
			'RESOURCES_FOLDER' => 'resources',
			'DEFAULT_SERVER_PORT' => 52_323,
			'NS_PANEL_API_VERSION_V1' => '1',
			'STATE_NAME_KEY' => '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/',
			'CHANNEL_IDENTIFIER' => '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/',
		],
		'FastyBird\\Connector\\Shelly\\Constants' => [
			'RESOURCES_FOLDER' => 'resources',
			'VALUE_NOT_AVAILABLE' => 'n/a',
			'WRITE_DEBOUNCE_DELAY' => 2_000.0,
			'COAP_ADDRESS' => '224.0.1.187',
			'COAP_PORT' => 5_683,
			'STATE_READING_DELAY' => 300.0,
			'EVENT_ERROR' => 'error',
			'EVENT_CLOSED' => 'closed',
			'EVENT_MESSAGE' => 'message',
			'EVENT_CONNECTED' => 'connected',
			'EVENT_DISCONNECTED' => 'disconnected',
			'EVENT_LOST' => 'lost',
		],
		'FastyBird\\Connector\\Sonoff\\Constants' => [
			'RESOURCES_FOLDER' => 'resources',
			'COOLKIT_APP_ID' => '4s1FXKC9FaGfoqXhmXSJneb3qcm1gOak',
			'COOLKIT_APP_SECRET' => 'oKvCM06gvwkRbfetd6qWRrbC3rFrbIpV',
			'DEFAULT_APP_ID' => 'R8Oq3y0eSZSYdKccHlrQzT1ACCOUT9Gv',
			'DEFAULT_APP_SECRET' => '1ve5Qk9GXfUhKAn1svnKwpAlxXkMarru',
			'VALUE_NOT_AVAILABLE' => 'N/A',
			'CHANNEL_GROUP' => '/^(?P<group>[a-zA-Z]+)(_(?P<outlet>[0-9]+))?$/',
			'STATE_READING_DELAY' => 300.0,
			'HEARTBEAT_DELAY' => 150.0,
			'WRITE_DEBOUNCE_DELAY' => 2_000.0,
		],
		'FastyBird\\Connector\\Tuya\\Constants' => [
			'RESOURCES_FOLDER' => 'resources',
			'EVENT_MESSAGE' => 'message',
			'EVENT_ERROR' => 'error',
			'EVENT_DISCONNECTED' => 'disconnected',
			'EVENT_CONNECTED' => 'connected',
			'EVENT_LOST' => 'lost',
		],
		'FastyBird\\Connector\\Viera\\Constants' => [
			'KEYS_PROPERTIES' => [
				'NRC_TV-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_TV,
				'NRC_HOME-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_HOME,
				'NRC_CH_UP-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_CHANNEL_UP,
				'NRC_CH_DOWN-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_CHANNEL_DOWN,
				'NRC_VOLUP-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_VOLUME_UP,
				'NRC_VOLDOWN-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_VOLUME_DOWN,
				'NRC_UP-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_ARROW_UP,
				'NRC_DOWN-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_ARROW_DOWN,
				'NRC_LEFT-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_ARROW_LEFT,
				'NRC_RIGHT-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_ARROW_RIGHT,
				'NRC_D0-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_0,
				'NRC_D1-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_1,
				'NRC_D2-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_2,
				'NRC_D3-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_3,
				'NRC_D4-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_4,
				'NRC_D5-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_5,
				'NRC_D6-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_6,
				'NRC_D7-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_7,
				'NRC_D8-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_8,
				'NRC_D9-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_9,
				'NRC_RED-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_RED,
				'NRC_GREEN-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_GREEN,
				'NRC_YELLOW-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_YELLOW,
				'NRC_BLUE-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_BLUE,
				'NRC_ENTER-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_OK,
				'NRC_RETURN-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_BACK,
				'NRC_MENU-ONOFF' => Viera\Types\ChannelPropertyIdentifier::KEY_MENU,
			],
			'TV_IDENTIFIER' => 'TV',
			'TV_CODE' => 500,
			'MAX_HDMI_CODE' => 100,
			'MIN_APPLICATION_CODE' => 1_000,
		],
		'FastyBird\\Connector\\Virtual\\Constants' => [
			'MANUFACTURER' => 'FastyBird',
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants' => [
			'BRIDGE_TOPIC' => '%s/#',
			'MQTT_TOPIC_DELIMITER' => '/',
			'MQTT_API_QOS_0' => 0,
			'MQTT_API_QOS_1' => 1,
			'MQTT_API_QOS_2' => 2,
			'VALUE_NOT_AVAILABLE' => 'n/a',
			'CHANNEL_IDENTIFIER_REGEX' => '/^(?P<type>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/',
			'CHANNEL_SPECIAL_IDENTIFIER_REGEX' => '/^(?P<type>(light|switch|fan|cover|lock|climate){1})_(?P<subtype>(binary|numeric|enum|'
				. 'text|composite){1})_(?P<identifier>[a-z0-9_]+)$/',
			'IEEE_ADDRESS_REGEX' => '/^0x([a-zA-Z0-9]+)$/',
			'EVENT_CONNECT' => 'connect',
			'EVENT_DISCONNECT' => 'disconnect',
			'EVENT_CLOSE' => 'close',
			'EVENT_WARNING' => 'warning',
			'EVENT_ERROR' => 'error',
			'EVENT_MESSAGE' => 'message',
			'EVENT_PUBLISH' => 'publish',
			'EVENT_SUBSCRIBE' => 'subscribe',
			'EVENT_UNSUBSCRIBE' => 'unsubscribe',
		],
	];

	/**
	 * The old class => the old constant name => where its value lives now: a class constant, of
	 * any visibility, or a backed enum case.
	 */
	private const array PACKAGE_DESTINATIONS = [
		'FastyBird\\Module\\Devices\\Constants' => [
			'MODULE_DEVICES_PREFIX' => [Devices\DI\DevicesExtension::class, 'MODULE_DEVICES_PREFIX'],
			'ROUTE_NAME_DEVICES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICES'],
			'ROUTE_NAME_DEVICE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE'],
			'ROUTE_NAME_DEVICE_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_RELATIONSHIP'],
			'ROUTE_NAME_DEVICE_PARENTS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PARENTS'],
			'ROUTE_NAME_DEVICE_CHILDREN' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHILDREN'],
			'ROUTE_NAME_DEVICE_PROPERTIES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PROPERTIES'],
			'ROUTE_NAME_DEVICE_PROPERTY' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PROPERTY'],
			'ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP'],
			'ROUTE_NAME_DEVICE_PROPERTY_CHILDREN' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PROPERTY_CHILDREN'],
			'ROUTE_NAME_DEVICE_PROPERTY_STATE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_PROPERTY_STATE'],
			'ROUTE_NAME_DEVICE_CONTROLS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CONTROLS'],
			'ROUTE_NAME_DEVICE_CONTROL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CONTROL'],
			'ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP'],
			'ROUTE_NAME_DEVICE_CHANNELS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNELS'],
			'ROUTE_NAME_DEVICE_CHANNEL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL'],
			'ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP'],
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES'],
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY'],
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP'],
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN'],
			'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE'],
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROLS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_CONTROLS'],
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_CONTROL'],
			'ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP'],
			'ROUTE_NAME_CHANNELS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNELS'],
			'ROUTE_NAME_CHANNEL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL'],
			'ROUTE_NAME_CHANNEL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_RELATIONSHIP'],
			'ROUTE_NAME_CHANNEL_PROPERTIES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_PROPERTIES'],
			'ROUTE_NAME_CHANNEL_PROPERTY' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_PROPERTY'],
			'ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP'],
			'ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN'],
			'ROUTE_NAME_CHANNEL_PROPERTY_STATE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_PROPERTY_STATE'],
			'ROUTE_NAME_CHANNEL_CONTROLS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_CONTROLS'],
			'ROUTE_NAME_CHANNEL_CONTROL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_CONTROL'],
			'ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP'],
			'ROUTE_NAME_CONNECTORS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTORS'],
			'ROUTE_NAME_CONNECTOR' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR'],
			'ROUTE_NAME_CONNECTOR_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_RELATIONSHIP'],
			'ROUTE_NAME_CONNECTOR_PROPERTIES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_PROPERTIES'],
			'ROUTE_NAME_CONNECTOR_PROPERTY' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_PROPERTY'],
			'ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP'],
			'ROUTE_NAME_CONNECTOR_PROPERTY_STATE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_PROPERTY_STATE'],
			'ROUTE_NAME_CONNECTOR_CONTROLS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_CONTROLS'],
			'ROUTE_NAME_CONNECTOR_CONTROL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_CONTROL'],
			'ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP'],
			'ROUTE_NAME_CONNECTOR_DEVICES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICES'],
			'ROUTE_NAME_CONNECTOR_DEVICE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE'],
			'ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP'],
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES'],
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY'],
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP' => [
				Devices\Router\ApiRoutes::class,
				'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP',
			],
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN'],
			'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE'],
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS'],
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL' => [Devices\Router\ApiRoutes::class, 'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL'],
			'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP' => [
				Devices\Router\ApiRoutes::class,
				'ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP',
			],
			'ROUTING_PREFIX' => [Devices\Types\RoutingKey::class, 'PREFIX'],
			'MESSAGE_BUS_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'REPORTED_PREFIX'],
			'MESSAGE_BUS_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CREATED_PREFIX'],
			'MESSAGE_BUS_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'UPDATED_PREFIX'],
			'MESSAGE_BUS_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DELETED_PREFIX'],
			'MESSAGE_BUS_DEVICE_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_DOCUMENT_CREATED'],
			'MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_DOCUMENT_DELETED'],
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_PROPERTY_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_PROPERTY_DOCUMENT_CREATED'],
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_PROPERTY_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_PROPERTY_DOCUMENT_DELETED'],
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED',
			],
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'DEVICE_PROPERTY_STATE_DOCUMENT_CREATED',
			],
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED',
			],
			'MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'DEVICE_PROPERTY_STATE_DOCUMENT_DELETED',
			],
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_CONTROL_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_CONTROL_DOCUMENT_CREATED'],
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_CONTROL_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_CONTROL_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CHANNEL_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_PROPERTY_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_PROPERTY_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_PROPERTY_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_PROPERTY_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED',
			],
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED',
			],
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED',
			],
			'MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED',
			],
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_CONTROL_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_CONTROL_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_CONTROL_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_CONTROL_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CONNECTOR_PROPERTY_DOCUMENT_REPORTED',
			],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_PROPERTY_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_PROPERTY_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_PROPERTY_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED',
			],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED',
			],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED',
			],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
				Devices\Types\RoutingKey::class,
				'CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED',
			],
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_CONTROL_DOCUMENT_REPORTED'],
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_CONTROL_DOCUMENT_CREATED'],
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_CONTROL_DOCUMENT_UPDATED'],
			'MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_CONTROL_DOCUMENT_DELETED'],
			'MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_CONTROL_ACTION'],
			'MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CONNECTOR_PROPERTY_ACTION'],
			'MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_CONTROL_ACTION'],
			'MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'DEVICE_PROPERTY_ACTION'],
			'MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_CONTROL_ACTION'],
			'MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY' => [Devices\Types\RoutingKey::class, 'CHANNEL_PROPERTY_ACTION'],
			'VALUE_FORMAT_NUMBER_RANGE' => [Devices\Entities\Property::class, 'VALUE_FORMAT_NUMBER_RANGE'],
			'VALUE_FORMAT_STRING_ENUM' => [Devices\Entities\Property::class, 'VALUE_FORMAT_STRING_ENUM'],
			'VALUE_FORMAT_COMBINED_ENUM' => [Devices\Entities\Property::class, 'VALUE_FORMAT_COMBINED_ENUM'],
			'MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Subscribers\ModuleEntities::class,
				'MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING',
			],
			'MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Subscribers\ModuleEntities::class,
				'MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING',
			],
			'MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
				Devices\Subscribers\ModuleEntities::class,
				'MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING',
			],
		],
		'FastyBird\\Addon\\VirtualThermostat\\Constants' => [
			'MANUFACTURER' => [VirtualThermostat\Entities\Devices\Device::class, 'MANUFACTURER'],
			'PRESET_CHANNEL_PATTERN' => [VirtualThermostat\Drivers\Thermostat::class, 'PRESET_CHANNEL_PATTERN'],
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants' => [
			'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
				ShellyConnectorHomeKitConnector\DI\ShellyConnectorHomeKitConnectorExtension::class,
				'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX',
			],
			'MANUFACTURER' => [ShellyConnectorHomeKitConnector\Builders\Builder::class, 'MANUFACTURER'],
			'MODEL' => [ShellyConnectorHomeKitConnector\Builders\Builder::class, 'MODEL'],
			'ROUTE_NAME_BRIDGES' => [ShellyConnectorHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGES'],
			'ROUTE_NAME_BRIDGE' => [ShellyConnectorHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGE'],
			'RESOURCES_FOLDER' => [ShellyConnectorHomeKitConnector\Mapping\Builder::class, 'RESOURCES_FOLDER'],
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants' => [
			'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX' => [
				VieraConnectorHomeKitConnector\DI\VieraConnectorHomeKitConnectorExtension::class,
				'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX',
			],
			'MANUFACTURER' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'MANUFACTURER'],
			'MODEL' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'MODEL'],
			'ROUTE_NAME_BRIDGES' => [VieraConnectorHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGES'],
			'ROUTE_NAME_BRIDGE' => [VieraConnectorHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGE'],
			'DEFAULT_ACTIVE_IDENTIFIER' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'DEFAULT_ACTIVE_IDENTIFIER'],
			'DEFAULT_ACTIVE_CODE' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'DEFAULT_ACTIVE_CODE'],
			'RESOURCES_FOLDER' => [VieraConnectorHomeKitConnector\Mapping\Builder::class, 'RESOURCES_FOLDER'],
			'INPUT_SOURCE_TYPE_TV' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'INPUT_SOURCE_TYPE_TV'],
			'INPUT_SOURCE_TYPE_HDMI' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'INPUT_SOURCE_TYPE_HDMI'],
			'INPUT_SOURCE_TYPE_APPLICATION' => [VieraConnectorHomeKitConnector\Builders\Builder::class, 'INPUT_SOURCE_TYPE_APPLICATION'],
		],
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants' => [
			'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX' => [
				VirtualThermostatAddonHomeKitConnector\DI\VirtualThermostatAddonHomeKitConnectorExtension::class,
				'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX',
			],
			'MODEL' => [VirtualThermostatAddonHomeKitConnector\Builders\Builder::class, 'MODEL'],
			'MANUFACTURER' => [VirtualThermostatAddonHomeKitConnector\Builders\Builder::class, 'MANUFACTURER'],
			'ROUTE_NAME_BRIDGES' => [VirtualThermostatAddonHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGES'],
			'ROUTE_NAME_BRIDGE' => [VirtualThermostatAddonHomeKitConnector\Router\ApiRoutes::class, 'ROUTE_NAME_BRIDGE'],
		],
		'FastyBird\\Connector\\FbMqtt\\Constants' => [
			'MQTT_TOPIC_DELIMITER' => [FbMqtt\API\V1Builder::class, 'MQTT_TOPIC_DELIMITER'],
			'MQTT_API_PREFIX' => [FbMqtt\API\V1Builder::class, 'MQTT_API_PREFIX'],
			'MQTT_API_V1_VERSION_PREFIX' => [FbMqtt\API\V1Builder::class, 'MQTT_API_V1_VERSION_PREFIX'],
			'MQTT_API_QOS_0' => [FbMqtt\API\Client::class, 'MQTT_API_QOS_0'],
			'MQTT_API_QOS_1' => [FbMqtt\API\Client::class, 'MQTT_API_QOS_1'],
			'MQTT_API_QOS_2' => [FbMqtt\API\Client::class, 'MQTT_API_QOS_2'],
			'PAYLOAD_BOOL_TRUE_VALUE' => [FbMqtt\Queue\Messages\PropertyAttribute::class, 'PAYLOAD_BOOL_TRUE_VALUE'],
			'PAYLOAD_BOOL_FALSE_VALUE' => [FbMqtt\Queue\Messages\PropertyAttribute::class, 'PAYLOAD_BOOL_FALSE_VALUE'],
			'VALUE_NOT_SET' => [FbMqtt\Queue\Messages\Property::class, 'VALUE_NOT_SET'],
			'DEFAULT_SERVER_ADDRESS' => [FbMqtt\Entities\Connectors\Connector::class, 'DEFAULT_SERVER_ADDRESS'],
			'DEFAULT_SERVER_PORT' => [FbMqtt\Entities\Connectors\Connector::class, 'DEFAULT_SERVER_PORT'],
			'DEFAULT_SERVER_SECURED_PORT' => [FbMqtt\Entities\Connectors\Connector::class, 'DEFAULT_SERVER_SECURED_PORT'],
			'WRITE_DEBOUNCE_DELAY' => [FbMqtt\Queue\Consumers\WriteV1ChannelPropertyState::class, 'WRITE_DEBOUNCE_DELAY'],
		],
		'FastyBird\\Connector\\HomeKit\\Constants' => [
			'PACKAGE_NAME' => [HomeKit\Protocol\Loader::class, 'PACKAGE_NAME'],
			'DEFAULT_MANUFACTURER' => [HomeKit\Protocol\Loader::class, 'DEFAULT_MANUFACTURER'],
			'DEFAULT_BRIDGE_MODEL' => [HomeKit\Protocol\Loader::class, 'DEFAULT_BRIDGE_MODEL'],
			'DEFAULT_DEVICE_MODEL' => [HomeKit\Protocol\Loader::class, 'DEFAULT_DEVICE_MODEL'],
			'BASE_UUID' => [HomeKit\Helpers\Protocol::class, 'BASE_UUID'],
			'RESOURCES_FOLDER' => [HomeKit\Helpers\Loader::class, 'RESOURCES_FOLDER'],
			'DEFAULT_CONFIG_VERSION' => [HomeKit\Entities\Connectors\Connector::class, 'DEFAULT_CONFIG_VERSION'],
			'DEFAULT_PORT' => [HomeKit\Entities\Connectors\Connector::class, 'DEFAULT_PORT'],
			'MAX_CONFIG_VERSION' => [HomeKit\Entities\Connectors\Connector::class, 'MAX_CONFIG_VERSION'],
			'STANDALONE_AID' => [HomeKit\Protocol\Accessories\Accessory::class, 'STANDALONE_AID'],
			'HAP_PROTOCOL_VERSION' => [HomeKit\Protocol\Loader::class, 'HAP_PROTOCOL_VERSION'],
			'HAP_PROTOCOL_SHORT_VERSION' => [HomeKit\Servers\Mdns::class, 'HAP_PROTOCOL_SHORT_VERSION'],
			'VERSION_REGEXP' => [HomeKit\Protocol\Loader::class, 'VERSION_REGEXP'],
			'SERVICE_IDENTIFIER' => [HomeKit\Entities\Channels\Channel::class, 'SERVICE_IDENTIFIER'],
		],
		'FastyBird\\Connector\\Modbus\\Constants' => [
			'DEFAULT_RTU_SERIAL_INTERFACE' => [Modbus\Entities\Connectors\Connector::class, 'DEFAULT_RTU_SERIAL_INTERFACE'],
			'MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST' => [Modbus\Clients\Client::class, 'MAX_ANALOG_REGISTERS_PER_MODBUS_REQUEST'],
			'MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST' => [Modbus\Clients\Client::class, 'MAX_DISCRETE_REGISTERS_PER_MODBUS_REQUEST'],
			'WRITE_DEBOUNCE_DELAY' => [Modbus\Queue\Consumers\WriteChannelPropertyState::class, 'WRITE_DEBOUNCE_DELAY'],
			'READING_DELAY' => [Modbus\Entities\Channels\Channel::class, 'READING_DELAY'],
			'DEFAULT_TCP_PORT' => [Modbus\Entities\Devices\Device::class, 'DEFAULT_TCP_PORT'],
		],
		'FastyBird\\Connector\\NsPanel\\Constants' => [
			'RESOURCES_FOLDER' => [NsPanel\DI\NsPanelExtension::class, 'RESOURCES_FOLDER'],
			'DEFAULT_SERVER_PORT' => [NsPanel\Entities\Connectors\Connector::class, 'DEFAULT_SERVER_PORT'],
			'NS_PANEL_API_VERSION_V1' => [NsPanel\API\LanApi::class, 'NS_PANEL_API_VERSION_V1'],
			'STATE_NAME_KEY' => [NsPanel\Types\Capability::class, 'STATE_NAME_KEY'],
			'CHANNEL_IDENTIFIER' => [NsPanel\Entities\Channels\Channel::class, 'CHANNEL_IDENTIFIER'],
		],
		'FastyBird\\Connector\\Shelly\\Constants' => [
			'RESOURCES_FOLDER' => [Shelly\DI\ShellyExtension::class, 'RESOURCES_FOLDER'],
			'VALUE_NOT_AVAILABLE' => [Shelly\API\Messages\Response\Gen2\DeviceState::class, 'VALUE_NOT_AVAILABLE'],
			'WRITE_DEBOUNCE_DELAY' => [Shelly\Queue\Consumers\WriteChannelPropertyState::class, 'WRITE_DEBOUNCE_DELAY'],
			'COAP_ADDRESS' => [Shelly\API\Gen1Coap::class, 'COAP_ADDRESS'],
			'COAP_PORT' => [Shelly\API\Gen1Coap::class, 'COAP_PORT'],
			'STATE_READING_DELAY' => [Shelly\Entities\Devices\Device::class, 'STATE_READING_DELAY'],
			'EVENT_ERROR' => [Shelly\API\Gen2WsApi::class, 'EVENT_ERROR'],
			'EVENT_CLOSED' => [Shelly\API\Gen1Coap::class, 'EVENT_CLOSED'],
			'EVENT_MESSAGE' => [Shelly\API\Gen2WsApi::class, 'EVENT_MESSAGE'],
			'EVENT_CONNECTED' => [Shelly\API\Gen2WsApi::class, 'EVENT_CONNECTED'],
			'EVENT_DISCONNECTED' => [Shelly\API\Gen2WsApi::class, 'EVENT_DISCONNECTED'],
			'EVENT_LOST' => [Shelly\API\Gen2WsApi::class, 'EVENT_LOST'],
		],
		'FastyBird\\Connector\\Sonoff\\Constants' => [
			'RESOURCES_FOLDER' => [Sonoff\DI\SonoffExtension::class, 'RESOURCES_FOLDER'],
			'COOLKIT_APP_ID' => [Sonoff\Entities\Connectors\Connector::class, 'COOLKIT_APP_ID'],
			'COOLKIT_APP_SECRET' => [Sonoff\Entities\Connectors\Connector::class, 'COOLKIT_APP_SECRET'],
			'DEFAULT_APP_ID' => [Sonoff\Entities\Connectors\Connector::class, 'DEFAULT_APP_ID'],
			'DEFAULT_APP_SECRET' => [Sonoff\Entities\Connectors\Connector::class, 'DEFAULT_APP_SECRET'],
			'VALUE_NOT_AVAILABLE' => [Sonoff\Commands\Install::class, 'VALUE_NOT_AVAILABLE'],
			'CHANNEL_GROUP' => [Sonoff\Queue\Consumers\WriteChannelPropertyState::class, 'CHANNEL_GROUP'],
			'STATE_READING_DELAY' => [Sonoff\Entities\Devices\Device::class, 'STATE_READING_DELAY'],
			'HEARTBEAT_DELAY' => [Sonoff\Helpers\Device::class, 'HEARTBEAT_DELAY'],
			'WRITE_DEBOUNCE_DELAY' => [Sonoff\Queue\Consumers\WriteChannelPropertyState::class, 'WRITE_DEBOUNCE_DELAY'],
		],
		'FastyBird\\Connector\\Tuya\\Constants' => [
			'RESOURCES_FOLDER' => [Tuya\DI\TuyaExtension::class, 'RESOURCES_FOLDER'],
			'EVENT_MESSAGE' => [Tuya\API\LocalApi::class, 'EVENT_MESSAGE'],
			'EVENT_ERROR' => [Tuya\API\LocalApi::class, 'EVENT_ERROR'],
			'EVENT_DISCONNECTED' => [Tuya\API\LocalApi::class, 'EVENT_DISCONNECTED'],
			'EVENT_CONNECTED' => [Tuya\API\LocalApi::class, 'EVENT_CONNECTED'],
			'EVENT_LOST' => [Tuya\API\LocalApi::class, 'EVENT_LOST'],
		],
		'FastyBird\\Connector\\Viera\\Constants' => [
			'KEYS_PROPERTIES' => [Viera\Types\ActionKey::class, 'KEYS_PROPERTIES'],
			'TV_IDENTIFIER' => [Viera\Entities\Devices\Device::class, 'TV_IDENTIFIER'],
			'TV_CODE' => [Viera\Entities\Devices\Device::class, 'TV_CODE'],
			'MAX_HDMI_CODE' => [Viera\Entities\Devices\Device::class, 'MAX_HDMI_CODE'],
			'MIN_APPLICATION_CODE' => [Viera\Entities\Devices\Device::class, 'MIN_APPLICATION_CODE'],
		],
		'FastyBird\\Connector\\Virtual\\Constants' => [
			'MANUFACTURER' => [Virtual\Subscribers\Properties::class, 'MANUFACTURER'],
		],
		'FastyBird\\Connector\\Zigbee2Mqtt\\Constants' => [
			'BRIDGE_TOPIC' => [Zigbee2Mqtt\API\MqttBuilder::class, 'BRIDGE_TOPIC'],
			'MQTT_TOPIC_DELIMITER' => [Zigbee2Mqtt\API\MqttBuilder::class, 'MQTT_TOPIC_DELIMITER'],
			'MQTT_API_QOS_0' => [Zigbee2Mqtt\API\Client::class, 'MQTT_API_QOS_0'],
			'MQTT_API_QOS_1' => [Zigbee2Mqtt\API\Client::class, 'MQTT_API_QOS_1'],
			'MQTT_API_QOS_2' => [Zigbee2Mqtt\API\Client::class, 'MQTT_API_QOS_2'],
			'VALUE_NOT_AVAILABLE' => [Zigbee2Mqtt\Queue\Messages\Exposes\Type::class, 'VALUE_NOT_AVAILABLE'],
			'CHANNEL_IDENTIFIER_REGEX' => [Zigbee2Mqtt\Entities\Channels\Channel::class, 'CHANNEL_IDENTIFIER_REGEX'],
			'CHANNEL_SPECIAL_IDENTIFIER_REGEX' => [Zigbee2Mqtt\Entities\Channels\Channel::class, 'CHANNEL_SPECIAL_IDENTIFIER_REGEX'],
			'IEEE_ADDRESS_REGEX' => [Zigbee2Mqtt\Queue\Consumers\StoreDeviceState::class, 'IEEE_ADDRESS_REGEX'],
			'EVENT_CONNECT' => [Zigbee2Mqtt\API\Client::class, 'EVENT_CONNECT'],
			'EVENT_DISCONNECT' => [Zigbee2Mqtt\API\Client::class, 'EVENT_DISCONNECT'],
			'EVENT_CLOSE' => [Zigbee2Mqtt\API\Client::class, 'EVENT_CLOSE'],
			'EVENT_WARNING' => [Zigbee2Mqtt\API\Client::class, 'EVENT_WARNING'],
			'EVENT_ERROR' => [Zigbee2Mqtt\API\Client::class, 'EVENT_ERROR'],
			'EVENT_MESSAGE' => [Zigbee2Mqtt\API\Client::class, 'EVENT_MESSAGE'],
			'EVENT_PUBLISH' => [Zigbee2Mqtt\API\Client::class, 'EVENT_PUBLISH'],
			'EVENT_SUBSCRIBE' => [Zigbee2Mqtt\API\Client::class, 'EVENT_SUBSCRIBE'],
			'EVENT_UNSUBSCRIBE' => [Zigbee2Mqtt\API\Client::class, 'EVENT_UNSUBSCRIBE'],
		],
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
	 * Each package constant is read under its old name: one missing from PACKAGE_DESTINATIONS or not
	 * in PACKAGE_VALUES fails the assertion, and so does a value that changed on the way.
	 *
	 * @throws ReflectionException
	 */
	public function testEveryPackageConstantHasItsPinnedValue(): void
	{
		$actual = [];

		foreach (self::PACKAGE_DESTINATIONS as $package => $destinations) {
			foreach ($destinations as $name => [$class, $member]) {
				$value = self::constantAt($class, $member);

				if ($name === 'RESOURCES_FOLDER' && is_string($value)) {
					$value = self::belowPackage($class, $value);
				}

				$actual[$package][$name] = $value;
			}
		}

		self::assertSame(self::PACKAGE_VALUES, $actual);
	}

	/**
	 * Each of Core's 60 is read above, under its old name: testEveryConstantHasItsPinnedValue fails
	 * on any name missing from DESTINATIONS or not in VALUES, and the package test does the same for
	 * the packages. What is left to pin is the classes themselves.
	 */
	public function testTheDissolvedConstantsClassesAreGone(): void
	{
		$present = [];

		foreach (['FastyBird\\Core\\Constants', ...array_keys(self::PACKAGE_VALUES)] as $class) {
			if (self::classExists($class)) {
				$present[] = $class;
			}
		}

		self::assertSame([], $present);
	}

	/**
	 * Asks the autoloader. PHPStan knows the dissolved classes are gone and would call a direct
	 * class_exists() on their literal names always false, so the name reaches it as a plain string.
	 */
	private static function classExists(string $class): bool
	{
		return class_exists($class);
	}

	/**
	 * @param class-string $class
	 *
	 * @throws ReflectionException
	 */
	private static function valueAt(string $class, string $member): string|int|float|null
	{
		$value = self::constantAt($class, $member);

		return is_array($value) ? null : $value;
	}

	/**
	 * A backed enum case is a class constant too, whose value is the case object.
	 *
	 * @param class-string $class
	 *
	 * @return array<mixed>|string|int|float|null
	 *
	 * @throws ReflectionException
	 */
	private static function constantAt(string $class, string $member): array|string|int|float|null
	{
		$reflection = new ReflectionClass($class);

		if (!$reflection->hasConstant($member)) {
			return null;
		}

		$value = $reflection->getConstant($member);

		if ($value instanceof BackedEnum) {
			return $value->value;
		}

		return is_string($value) || is_int($value) || is_float($value) || is_array($value) ? $value : null;
	}

	/**
	 * The directory $path resolves to, relative to the root of the package that declares $class:
	 * the nearest directory above the declaring file that holds a composer.json. Null when $path
	 * does not exist or lies outside that package.
	 *
	 * @param class-string $class
	 *
	 * @throws ReflectionException
	 */
	private static function belowPackage(string $class, string $path): string|null
	{
		$file = (new ReflectionClass($class))->getFileName();

		if ($file === false) {
			return null;
		}

		$package = dirname($file);

		while (!is_file($package . DIRECTORY_SEPARATOR . 'composer.json')) {
			if (dirname($package) === $package) {
				return null;
			}

			$package = dirname($package);
		}

		$package = realpath($package);
		$resolved = realpath($path);

		if ($package === false || $resolved === false || !str_starts_with($resolved, $package . DIRECTORY_SEPARATOR)) {
			return null;
		}

		return substr($resolved, strlen($package) + 1);
	}

}
