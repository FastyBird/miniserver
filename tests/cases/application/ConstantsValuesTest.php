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
use function array_keys;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function is_array;
use function is_string;

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
				$actual[$package][$name] = self::constantAt($class, $member);
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
	private static function valueAt(string $class, string $member): string|int|null
	{
		$value = self::constantAt($class, $member);

		return is_array($value) ? null : $value;
	}

	/**
	 * A backed enum case is a class constant too, whose value is the case object.
	 *
	 * @param class-string $class
	 *
	 * @return array<mixed>|string|int|null
	 *
	 * @throws ReflectionException
	 */
	private static function constantAt(string $class, string $member): array|string|int|null
	{
		$reflection = new ReflectionClass($class);

		if (!$reflection->hasConstant($member)) {
			return null;
		}

		$value = $reflection->getConstant($member);

		if ($value instanceof BackedEnum) {
			return $value->value;
		}

		return is_string($value) || is_array($value) ? $value : null;
	}

}
