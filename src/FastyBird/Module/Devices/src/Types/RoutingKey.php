<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Types;

use FastyBird\Core\Exchange\Publisher;

/**
 * Exchange routing keys of the module's documents and actions
 *
 * The constants are the prefixes the keys are built from. They are not keys themselves, so they
 * are not cases: a consumer matches a whole family with str_starts_with() against one of them.
 */
enum RoutingKey: string
{

	public const string PREFIX = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.module.document';

	public const string REPORTED_PREFIX = self::PREFIX . '.reported.';

	public const string CREATED_PREFIX = self::PREFIX . '.created.';

	public const string UPDATED_PREFIX = self::PREFIX . '.updated.';

	public const string DELETED_PREFIX = self::PREFIX . '.deleted.';

	// DEVICES
	case DEVICE_DOCUMENT_REPORTED = self::PREFIX . '.reported.device';

	case DEVICE_DOCUMENT_CREATED = self::PREFIX . '.created.device';

	case DEVICE_DOCUMENT_UPDATED = self::PREFIX . '.updated.device';

	case DEVICE_DOCUMENT_DELETED = self::PREFIX . '.deleted.device';

	// DEVICES PROPERTIES
	case DEVICE_PROPERTY_DOCUMENT_REPORTED = self::PREFIX . '.reported.device.property';

	case DEVICE_PROPERTY_DOCUMENT_CREATED = self::PREFIX . '.created.device.property';

	case DEVICE_PROPERTY_DOCUMENT_UPDATED = self::PREFIX . '.updated.device.property';

	case DEVICE_PROPERTY_DOCUMENT_DELETED = self::PREFIX . '.deleted.device.property';

	// DEVICES PROPERTIES STATES
	case DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED = self::PREFIX . '.reported.device.property.state';

	case DEVICE_PROPERTY_STATE_DOCUMENT_CREATED = self::PREFIX . '.created.device.property.state';

	case DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED = self::PREFIX . '.updated.device.property.state';

	case DEVICE_PROPERTY_STATE_DOCUMENT_DELETED = self::PREFIX . '.deleted.device.property.state';

	// DEVICES CONTROLS
	case DEVICE_CONTROL_DOCUMENT_REPORTED = self::PREFIX . '.reported.device.control';

	case DEVICE_CONTROL_DOCUMENT_CREATED = self::PREFIX . '.created.device.control';

	case DEVICE_CONTROL_DOCUMENT_UPDATED = self::PREFIX . '.updated.device.control';

	case DEVICE_CONTROL_DOCUMENT_DELETED = self::PREFIX . '.deleted.device.control';

	// CHANNELS
	case CHANNEL_DOCUMENT_REPORTED = self::PREFIX . '.reported.channel';

	case CHANNEL_DOCUMENT_CREATED = self::PREFIX . '.created.channel';

	case CHANNEL_DOCUMENT_UPDATED = self::PREFIX . '.updated.channel';

	case CHANNEL_DOCUMENT_DELETED = self::PREFIX . '.deleted.channel';

	// CHANNELS PROPERTIES
	case CHANNEL_PROPERTY_DOCUMENT_REPORTED = self::PREFIX . '.reported.channel.property';

	case CHANNEL_PROPERTY_DOCUMENT_CREATED = self::PREFIX . '.created.channel.property';

	case CHANNEL_PROPERTY_DOCUMENT_UPDATED = self::PREFIX . '.updated.channel.property';

	case CHANNEL_PROPERTY_DOCUMENT_DELETED = self::PREFIX . '.deleted.channel.property';

	// CHANNELS PROPERTIES STATES
	case CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED = self::PREFIX . '.reported.channel.property.state';

	case CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED = self::PREFIX . '.created.channel.property.state';

	case CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED = self::PREFIX . '.updated.channel.property.state';

	case CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED = self::PREFIX . '.deleted.channel.property.state';

	// CHANNELS CONTROLS
	case CHANNEL_CONTROL_DOCUMENT_REPORTED = self::PREFIX . '.reported.channel.control';

	case CHANNEL_CONTROL_DOCUMENT_CREATED = self::PREFIX . '.created.channel.control';

	case CHANNEL_CONTROL_DOCUMENT_UPDATED = self::PREFIX . '.updated.channel.control';

	case CHANNEL_CONTROL_DOCUMENT_DELETED = self::PREFIX . '.deleted.channel.control';

	// CONNECTORS
	case CONNECTOR_DOCUMENT_REPORTED = self::PREFIX . '.reported.connector';

	case CONNECTOR_DOCUMENT_CREATED = self::PREFIX . '.created.connector';

	case CONNECTOR_DOCUMENT_UPDATED = self::PREFIX . '.updated.connector';

	case CONNECTOR_DOCUMENT_DELETED = self::PREFIX . '.deleted.connector';

	// CONNECTORS PROPERTIES
	case CONNECTOR_PROPERTY_DOCUMENT_REPORTED = self::PREFIX . '.reported.connector.property';

	case CONNECTOR_PROPERTY_DOCUMENT_CREATED = self::PREFIX . '.created.connector.property';

	case CONNECTOR_PROPERTY_DOCUMENT_UPDATED = self::PREFIX . '.updated.connector.property';

	case CONNECTOR_PROPERTY_DOCUMENT_DELETED = self::PREFIX . '.deleted.connector.property';

	// CONNECTORS PROPERTIES STATES
	case CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED = self::PREFIX . '.reported.connector.property.state';

	case CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED = self::PREFIX . '.created.connector.property.state';

	case CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED = self::PREFIX . '.updated.connector.property.state';

	case CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED = self::PREFIX . '.deleted.connector.property.state';

	// CONNECTORS CONTROLS
	case CONNECTOR_CONTROL_DOCUMENT_REPORTED = self::PREFIX . '.reported.connector.control';

	case CONNECTOR_CONTROL_DOCUMENT_CREATED = self::PREFIX . '.created.connector.control';

	case CONNECTOR_CONTROL_DOCUMENT_UPDATED = self::PREFIX . '.updated.connector.control';

	case CONNECTOR_CONTROL_DOCUMENT_DELETED = self::PREFIX . '.deleted.connector.control';

	// ACTIONS
	case CONNECTOR_CONTROL_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.connector.control';

	case CONNECTOR_PROPERTY_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.connector.property';

	case DEVICE_CONTROL_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.device.control';

	case DEVICE_PROPERTY_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.device.property';

	case CHANNEL_CONTROL_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.channel.control';

	case CHANNEL_PROPERTY_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.channel.property';

}
