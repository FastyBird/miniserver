<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Types\Sources;

/**
 * Bridges sources types
 */
enum Bridge: string implements Source
{

	case NOT_SPECIFIED = '*';

	case REDISDB_PLUGIN_DEVICES_MODULE = 'com.fastybird.redisdb-plugin-devices-module-bridge';

	case REDISDB_PLUGIN_TRIGGERS_MODULE = 'com.fastybird.redisdb-plugin-triggers-module-bridge';

	case VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR = 'com.fastybird.virtual-thermostat-addon-homekit-connector-bridge';

	case SHELLY_CONNECTOR_HOMEKIT_CONNECTOR = 'com.fastybird.shelly-connector-homekit-connector-bridge';

	case VIERA_CONNECTOR_HOMEKIT_CONNECTOR = 'com.fastybird.viera-connector-homekit-connector-bridge';

	case DEVICES_MODULE_UI_MODULE = 'com.fastybird.devices-module-ui-module-bridge';

}
