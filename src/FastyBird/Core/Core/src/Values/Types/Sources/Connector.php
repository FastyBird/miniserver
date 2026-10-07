<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Connectors sources types
 */
enum Connector: string implements Source
{

	case NOT_SPECIFIED = '*';

	case FB_BUS = 'com.fastybird.fb-bus-connector';

	case FB_MQTT = 'com.fastybird.fb-mqtt-connector';

	case SHELLY = 'com.fastybird.shelly-connector';

	case TUYA = 'com.fastybird.tuya-connector';

	case SONOFF = 'com.fastybird.sonoff-connector';

	case MODBUS = 'com.fastybird.modbus-connector';

	case HOMEKIT = 'com.fastybird.homekit-connector';

	case VIRTUAL = 'com.fastybird.virtual-connector';

	case TERMINAL = 'com.fastybird.terminal-connector';

	case VIERA = 'com.fastybird.viera-connector';

	case NS_PANEL = 'com.fastybird.ns-panel-connector';

	case ZIGBEE2MQTT = 'com.fastybird.zigbee2mqtt-connector';

}
