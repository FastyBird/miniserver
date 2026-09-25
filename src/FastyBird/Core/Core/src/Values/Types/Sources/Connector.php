<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

use FastyBird\Core\Constants;

/**
 * Connectors sources types
 */
enum Connector: string implements Source
{

	case NOT_SPECIFIED = Constants::NOT_SPECIFIED_SOURCE;

	case FB_BUS = Constants::CONNECTOR_FB_BUS_SOURCE;

	case FB_MQTT = Constants::CONNECTOR_FB_MQTT_SOURCE;

	case SHELLY = Constants::CONNECTOR_SHELLY_SOURCE;

	case TUYA = Constants::CONNECTOR_TUYA_SOURCE;

	case SONOFF = Constants::CONNECTOR_SONOFF_SOURCE;

	case MODBUS = Constants::CONNECTOR_MODBUS_SOURCE;

	case HOMEKIT = Constants::CONNECTOR_HOMEKIT_SOURCE;

	case VIRTUAL = Constants::CONNECTOR_VIRTUAL_SOURCE;

	case TERMINAL = Constants::CONNECTOR_TERMINAL_SOURCE;

	case VIERA = Constants::CONNECTOR_VIERA_SOURCE;

	case NS_PANEL = Constants::CONNECTOR_NS_PANEL_SOURCE;

	case ZIGBEE2MQTT = Constants::CONNECTOR_ZIGBEE2MQTT_SOURCE;

}
