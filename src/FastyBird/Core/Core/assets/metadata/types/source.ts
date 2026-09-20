export enum ModuleSource {
	NOT_SPECIFIED = '*',
	ACCOUNTS = 'com.fastybird.accounts-module',
	DEVICES = 'com.fastybird.devices-module',
	TRIGGERS = 'com.fastybird.triggers-module',
	UI = 'com.fastybird.ui-module',
}

export enum PluginSource {
	NOT_SPECIFIED = '*',
	COUCHDB_STORAGE = 'com.fastybird.couchdb-storage-plugin',
	RABBITMQ = 'com.fastybird.rabbitmq-plugin',
	REDISDB = 'com.fastybird.redisdb-plugin',
	REDISDB_CACHE = 'com.fastybird.redisdb-cache-plugin',
	WS_EXCHANGE = 'com.fastybird.ws-server-plugin',
	WEB_SERVER = 'com.fastybird.web-server-plugin',
	API_KEY = 'com.fastybird.api-key-plugin',
}

export enum ConnectorSource {
	NOT_SPECIFIED = '*',
	FB_BUS = 'com.fastybird.fb-bus-connector',
	FB_MQTT = 'com.fastybird.fb-mqtt-connector',
	SHELLY = 'com.fastybird.shelly-connector',
	TUYA = 'com.fastybird.tuya-connector',
	SONOFF = 'com.fastybird.sonoff-connector',
	MODBUS = 'com.fastybird.modbus-connector',
	HOMEKIT = 'com.fastybird.homekit-connector',
	VIRTUAL = 'com.fastybird.virtual-connector',
	TERMINAL = 'com.fastybird.terminal-connector',
	VIERA = 'com.fastybird.viera-connector',
	NS_PANEL = 'com.fastybird.ns-panel-connector',
	ZIGBEE2MQTT = 'com.fastybird.zigbee2mqtt-connector',
}

export enum AutomatorSource {
	NOT_SPECIFIED = '*',
	DEVICE_MODULE = 'com.fastybird.device-module-automator',
	DATE_TIME = 'com.fastybird.date-time-automator',
}

export enum BridgeSource {
	NOT_SPECIFIED = '*',
	REDISDB_PLUGIN_DEVICES_MODULE = 'com.fastybird.redisdb-plugin-devices-module-bridge',
	REDISDB_PLUGIN_TRIGGERS_MODULE = 'com.fastybird.redisdb-plugin-triggers-module-bridge',
	VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR = 'com.fastybird.virtual-thermostat-addon-homekit-connector-bridge',
	SHELLY_CONNECTOR_HOMEKIT_CONNECTOR = 'com.fastybird.shelly-connector-homekit-connector-bridge',
	DEVICES_MODULE_UI_MODULE = 'com.fastybird.devices-module-ui-module-bridge',
}

export enum AddonSource {
	NOT_SPECIFIED = '*',
	VIRTUAL_THERMOSTAT = 'com.fastybird.virtual-thermostat-addon',
}
