<?php declare(strict_types = 1);

/**
 * #699 (E7.5, #462): the Ui module's untyped class constants gain a native type. The change list
 * format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ f2e389c02 (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Module\\Ui\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_GROUP_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_GROUP_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_GROUP_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_GROUP_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_ROUTING_KEYS' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::MESSAGE_BUS_WIDGET_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARD' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARDS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARD_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARD_TAB' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARD_TABS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_DASHBOARD_TAB_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_GROUP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_GROUPS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_GROUP_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGETS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_DATA_SOURCE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_DATA_SOURCES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_DATA_SOURCE_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_DISPLAY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_DISPLAY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTE_NAME_WIDGET_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Constants::ROUTING_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\DI\\UiExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Actuator::WIDGET_GROUP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\AnalogActuator::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\AnalogSensor::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\DataSources\\Generic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\DigitalActuator::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\DigitalSensor::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\AnalogValue::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Button::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\ChartGraph::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\DigitalValue::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Gauge::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\GroupedButton::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Slider::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Sensor::WIDGET_GROUP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Router\\ApiRoutes::URL_DASHBOARD_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Router\\ApiRoutes::URL_WIDGET_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Dashboards\\Dashboard::RELATIONSHIPS_TABS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Dashboards\\Dashboard::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Dashboards\\Tabs\\Tab::RELATIONSHIPS_DASHBOARD' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Dashboards\\Tabs\\Tab::RELATIONSHIPS_WIDGETS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Dashboards\\Tabs\\Tab::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Groups\\Group::RELATIONSHIPS_WIDGETS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Groups\\Group::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\AnalogActuator::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\AnalogSensor::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\DataSources\\DataSource::RELATIONSHIPS_WIDGET' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\DataSources\\Generic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\DigitalActuator::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\DigitalSensor::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\AnalogValue::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\Button::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\ChartGraph::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\DigitalValue::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\Display::RELATIONSHIPS_WIDGET' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\Gauge::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\GroupedButton::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Display\\Slider::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Widget::RELATIONSHIPS_DATA_SOURCES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Widget::RELATIONSHIPS_DISPLAY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Widget::RELATIONSHIPS_GROUPS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Ui\\Schemas\\Widgets\\Widget::RELATIONSHIPS_TABS' => [
			'type' => 'string',
		],
	],
];
