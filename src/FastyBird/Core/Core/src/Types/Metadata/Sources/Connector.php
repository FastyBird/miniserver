<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           19.01.22
 */

namespace FastyBird\Core\Types\Metadata\Sources;

use FastyBird\Core\Constants\Metadata;

/**
 * Connectors sources types
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Connector: string implements Source
{

	case NOT_SPECIFIED = Metadata\Constants::NOT_SPECIFIED_SOURCE;

	case FB_BUS = Metadata\Constants::CONNECTOR_FB_BUS_SOURCE;

	case FB_MQTT = Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE;

	case SHELLY = Metadata\Constants::CONNECTOR_SHELLY_SOURCE;

	case TUYA = Metadata\Constants::CONNECTOR_TUYA_SOURCE;

	case SONOFF = Metadata\Constants::CONNECTOR_SONOFF_SOURCE;

	case MODBUS = Metadata\Constants::CONNECTOR_MODBUS_SOURCE;

	case HOMEKIT = Metadata\Constants::CONNECTOR_HOMEKIT_SOURCE;

	case VIRTUAL = Metadata\Constants::CONNECTOR_VIRTUAL_SOURCE;

	case TERMINAL = Metadata\Constants::CONNECTOR_TERMINAL_SOURCE;

	case VIERA = Metadata\Constants::CONNECTOR_VIERA_SOURCE;

	case NS_PANEL = Metadata\Constants::CONNECTOR_NS_PANEL_SOURCE;

	case ZIGBEE2MQTT = Metadata\Constants::CONNECTOR_ZIGBEE2MQTT_SOURCE;

}
