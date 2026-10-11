<?php declare(strict_types = 1);

/**
 * #699 (E7.5, #462): the Devices module's untyped class constants gain a native type. The change list
 * format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ f2e389c02 (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Module\\Devices\\Commands\\Connector::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Commands\\Diagnostics::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Commands\\Exchange::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Devices\\Constants::MESSAGE_BUS_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNELS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_PROPERTY_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CHANNEL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTORS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_PROPERTY_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_CONNECTOR_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNELS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PARENTS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PROPERTY_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_PROPERTY_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Constants::ROUTING_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\DI\\DevicesExtension::CONNECTOR_TYPE_TAG' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\DI\\DevicesExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Channels\\Generic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Channels\\Properties\\Dynamic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Channels\\Properties\\Mapped::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Channels\\Properties\\Variable::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Connectors\\Generic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Connectors\\Properties\\Dynamic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Connectors\\Properties\\Variable::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Devices\\Generic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Devices\\Properties\\Dynamic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Devices\\Properties\\Mapped::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Entities\\Devices\\Properties\\Variable::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::URL_CHANNEL_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::URL_CONNECTOR_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::URL_DEVICE_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Router\\ApiRoutes::URL_PROPERTY_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Channel::RELATIONSHIPS_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Channel::RELATIONSHIPS_DEVICE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Channel::RELATIONSHIPS_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Controls\\Control::RELATIONSHIPS_CHANNEL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Controls\\Control::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Generic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Dynamic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Mapped::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Property::RELATIONSHIPS_CHANNEL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Property::RELATIONSHIPS_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Property::RELATIONSHIPS_PARENT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Property::RELATIONSHIPS_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\States\\State::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\States\\State::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Channels\\Properties\\Variable::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Connector::RELATIONSHIPS_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Connector::RELATIONSHIPS_DEVICES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Connector::RELATIONSHIPS_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Controls\\Control::RELATIONSHIPS_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Controls\\Control::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Generic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\Dynamic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\Property::RELATIONSHIPS_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\Property::RELATIONSHIPS_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\States\\State::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\States\\State::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Connectors\\Properties\\Variable::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Controls\\Control::RELATIONSHIPS_DEVICE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Controls\\Control::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_CHANNELS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_CONNECTOR' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_PARENTS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Device::RELATIONSHIPS_PROPERTIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Generic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Dynamic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Mapped::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Property::RELATIONSHIPS_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Property::RELATIONSHIPS_DEVICE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Property::RELATIONSHIPS_PARENT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Property::RELATIONSHIPS_STATE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\States\\State::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\States\\State::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\Schemas\\Devices\\Properties\\Variable::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::ACTUAL_VALUE_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::CREATED_AT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::EXPECTED_VALUE_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::PENDING_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::UPDATED_AT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Devices\\States\\Property::VALID_FIELD' => [
			'type' => 'string',
		],
	],
];
