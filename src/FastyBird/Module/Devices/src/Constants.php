<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           18.03.20
 */

namespace FastyBird\Module\Devices;

use FastyBird\Core\Constants as CoreConstants;

/**
 * Service constants
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * MODULE API ROUTING
	 */

	public const ROUTE_NAME_DEVICES = 'devices';

	public const ROUTE_NAME_DEVICE = 'device';

	public const ROUTE_NAME_DEVICE_RELATIONSHIP = 'device.relationship';

	public const ROUTE_NAME_DEVICE_PARENTS = 'device.parents';

	public const ROUTE_NAME_DEVICE_CHILDREN = 'device.children';

	public const ROUTE_NAME_DEVICE_PROPERTIES = 'device.properties';

	public const ROUTE_NAME_DEVICE_PROPERTY = 'device.property';

	public const ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP = 'device.property.relationship';

	public const ROUTE_NAME_DEVICE_PROPERTY_CHILDREN = 'device.property.children';

	public const ROUTE_NAME_DEVICE_PROPERTY_STATE = 'device.property.state';

	public const ROUTE_NAME_DEVICE_CONTROLS = 'device.controls';

	public const ROUTE_NAME_DEVICE_CONTROL = 'device.control';

	public const ROUTE_NAME_DEVICE_CONTROL_RELATIONSHIP = 'device.control.relationship';

	public const ROUTE_NAME_DEVICE_CHANNELS = 'device.channels';

	public const ROUTE_NAME_DEVICE_CHANNEL = 'device.channel';

	public const ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP = 'device.channel.relationship';

	public const ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES = 'device.channel.properties';

	public const ROUTE_NAME_DEVICE_CHANNEL_PROPERTY = 'device.channel.property';

	public const ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP = 'device.channel.property.relationship';

	public const ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN = 'device.channel.property.children';

	public const ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE = 'device.channel.property.state';

	public const ROUTE_NAME_DEVICE_CHANNEL_CONTROLS = 'device.channel.controls';

	public const ROUTE_NAME_DEVICE_CHANNEL_CONTROL = 'device.channel.control';

	public const ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP = 'device.channel.control.relationship';

	public const ROUTE_NAME_CHANNELS = 'channels';

	public const ROUTE_NAME_CHANNEL = 'channel';

	public const ROUTE_NAME_CHANNEL_RELATIONSHIP = 'channel.relationship';

	public const ROUTE_NAME_CHANNEL_PROPERTIES = 'channel.properties';

	public const ROUTE_NAME_CHANNEL_PROPERTY = 'channel.property';

	public const ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP = 'channel.property.relationship';

	public const ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN = 'channel.property.children';

	public const ROUTE_NAME_CHANNEL_PROPERTY_STATE = 'channel.property.state';

	public const ROUTE_NAME_CHANNEL_CONTROLS = 'channel.controls';

	public const ROUTE_NAME_CHANNEL_CONTROL = 'channel.control';

	public const ROUTE_NAME_CHANNEL_CONTROL_RELATIONSHIP = 'channel.control.relationship';

	public const ROUTE_NAME_CONNECTORS = 'connectors';

	public const ROUTE_NAME_CONNECTOR = 'connector';

	public const ROUTE_NAME_CONNECTOR_RELATIONSHIP = 'connector.relationship';

	public const ROUTE_NAME_CONNECTOR_PROPERTIES = 'connector.properties';

	public const ROUTE_NAME_CONNECTOR_PROPERTY = 'connector.property';

	public const ROUTE_NAME_CONNECTOR_PROPERTY_RELATIONSHIP = 'connector.property.relationship';

	public const ROUTE_NAME_CONNECTOR_PROPERTY_STATE = 'connector.property.state';

	public const ROUTE_NAME_CONNECTOR_CONTROLS = 'connector.controls';

	public const ROUTE_NAME_CONNECTOR_CONTROL = 'connector.control';

	public const ROUTE_NAME_CONNECTOR_CONTROL_RELATIONSHIP = 'connector.control.relationship';

	public const ROUTE_NAME_CONNECTOR_DEVICES = 'connector.devices';

	public const ROUTE_NAME_CONNECTOR_DEVICE = 'connector.device';

	public const ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP = 'connector.device.relationship';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PARENTS = 'connector.device.parents';

	public const ROUTE_NAME_CONNECTOR_DEVICE_CHILDREN = 'connector.device.children';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES = 'connector.device.properties';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY = 'connector.device.property';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP = 'connector.device.property.relationship';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN = 'connector.device.property.children';

	public const ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE = 'connector.device.property.state';

	public const ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS = 'connector.device.controls';

	public const ROUTE_NAME_CONNECTOR_DEVICE_CONTROL = 'connector.device.control';

	public const ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP = 'connector.device.control.relationship';

	/**
	 * MODULE MESSAGE BUS
	 */

	public const ROUTING_PREFIX = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.module.document';

	public const MESSAGE_BUS_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.';

	public const MESSAGE_BUS_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.';

	public const MESSAGE_BUS_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.';

	public const MESSAGE_BUS_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.';

	// DEVICES
	public const MESSAGE_BUS_DEVICE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.device';

	public const MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.device';

	public const MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.device';

	public const MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.device';

	// DEVICES PROPERTIES
	public const MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.device.property';

	public const MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.device.property';

	// DEVICES PROPERTIES STATES
	public const MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . 'reported.device.property.state';

	public const MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.device.property.state';

	public const MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.device.property.state';

	public const MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.device.property.state';

	// DEVICES CONTROLS
	public const MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.device.control';

	public const MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.device.control';

	// CHANNELS
	public const MESSAGE_BUS_CHANNEL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.channel';

	public const MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.channel';

	public const MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.channel';

	public const MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.channel';

	// CHANNELS PROPERTIES
	public const MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.channel.property';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.channel.property';

	// CHANNELS PROPERTIES STATES
	public const MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.channel.property.state';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.channel.property.state';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.channel.property.state';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.channel.property.state';

	// CHANNELS CONTROLS
	public const MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.channel.control';

	public const MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.channel.control';

	// CONNECTORS
	public const MESSAGE_BUS_CONNECTOR_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.connector';

	public const MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.connector';

	public const MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.connector';

	public const MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.connector';

	// CONNECTORS PROPERTIES
	public const MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.connector.property';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.connector.property';

	// CONNECTORS PROPERTIES STATES
	public const MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.connector.property.state';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.connector.property.state';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.connector.property.state';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.connector.property.state';

	// CONNECTORS CONTROLS
	public const MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.connector.control';

	public const MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.connector.control';

	// ACTIONS
	public const MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.connector.control';

	public const MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.connector.property';

	public const MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.device.control';

	public const MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.device.property';

	public const MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.channel.control';

	public const MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY = CoreConstants::MESSAGE_BUS_PREFIX_KEY . '.action.channel.property';

	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => self::MESSAGE_BUS_CONNECTOR_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => self::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => self::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Device::class => self::MESSAGE_BUS_DEVICE_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => self::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => self::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Channel::class => self::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => self::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => self::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_CREATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => self::MESSAGE_BUS_CONNECTOR_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => self::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => self::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Device::class => self::MESSAGE_BUS_DEVICE_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => self::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => self::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Channel::class => self::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => self::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => self::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Connectors\Connector::class => self::MESSAGE_BUS_CONNECTOR_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Connectors\Properties\Property::class => self::MESSAGE_BUS_CONNECTOR_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Connectors\Controls\Control::class => self::MESSAGE_BUS_CONNECTOR_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Device::class => self::MESSAGE_BUS_DEVICE_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Properties\Property::class => self::MESSAGE_BUS_DEVICE_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Devices\Controls\Control::class => self::MESSAGE_BUS_DEVICE_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Channel::class => self::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Properties\Property::class => self::MESSAGE_BUS_CHANNEL_PROPERTY_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Channels\Controls\Control::class => self::MESSAGE_BUS_CHANNEL_CONTROL_DOCUMENT_DELETED_ROUTING_KEY,
		];

}
