import { InjectionKey } from 'vue';

import { ConnectorSource, StoreInjectionKey } from '@fastybird/miniserver-core';

import {
	IChannelControlsActions,
	IChannelControlsState,
	IChannelPropertiesActions,
	IChannelPropertiesState,
	IChannelsActions,
	IChannelsState,
	IConnectorControlsActions,
	IConnectorControlsState,
	IConnectorPlugin,
	IConnectorPropertiesActions,
	IConnectorPropertiesState,
	IConnectorsActions,
	IConnectorsState,
	IDeviceControlsActions,
	IDeviceControlsState,
	IDevicePropertiesActions,
	IDevicePropertiesState,
	IDevicesActions,
	IDevicesModuleMeta,
	IDevicesState,
} from './types';

export const metaKey: InjectionKey<IDevicesModuleMeta> = Symbol('devices-module_meta');

export const channelsStoreKey: StoreInjectionKey<string, IChannelsState, object, IChannelsActions> = Symbol('devices-module_store_channels');
export const channelControlsStoreKey: StoreInjectionKey<string, IChannelControlsState, object, IChannelControlsActions> = Symbol(
	'devices-module_store_channel_controls'
);
export const channelPropertiesStoreKey: StoreInjectionKey<string, IChannelPropertiesState, object, IChannelPropertiesActions> = Symbol(
	'devices-module_store_channel_properties'
);
export const connectorsStoreKey: StoreInjectionKey<string, IConnectorsState, object, IConnectorsActions> = Symbol('devices-module_store_connectors');
export const connectorControlsStoreKey: StoreInjectionKey<string, IConnectorControlsState, object, IConnectorControlsActions> = Symbol(
	'devices-module_store_connector_controls'
);
export const connectorPropertiesStoreKey: StoreInjectionKey<string, IConnectorPropertiesState, object, IConnectorPropertiesActions> = Symbol(
	'devices-module_store_connector_properties'
);
export const devicesStoreKey: StoreInjectionKey<string, IDevicesState, object, IDevicesActions> = Symbol('devices-module_store_devices');
export const deviceControlsStoreKey: StoreInjectionKey<string, IDeviceControlsState, object, IDeviceControlsActions> = Symbol(
	'devices-module_store_device_controls'
);
export const devicePropertiesStoreKey: StoreInjectionKey<string, IDevicePropertiesState, object, IDevicePropertiesActions> = Symbol(
	'devices-module_store_device_properties'
);

export const connectorPlugins: IConnectorPlugin[] = [
	{
		type: 'ns-panel-connector',
		source: ConnectorSource.NS_PANEL,
		name: 'NS Panel',
		description: 'FastyBird IoT connector for Sonoff NS Panel Pro devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'shelly-connector',
		source: ConnectorSource.SHELLY,
		name: 'Shelly',
		description: 'FastyBird IoT connector for Shelly devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'sonoff-connector',
		source: ConnectorSource.SONOFF,
		name: 'Sonoff',
		description: 'FastyBird IoT connector for Sonoff devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'tuya-connector',
		source: ConnectorSource.TUYA,
		name: 'Tuya',
		description: 'FastyBird IoT connector for Tuya devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'viera-connector',
		source: ConnectorSource.VIERA,
		name: 'Viera',
		description: 'FastyBird IoT connector for Panasonic Viera televisions',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
	{
		type: 'zigbee2mqtt-connector',
		source: ConnectorSource.ZIGBEE2MQTT,
		name: 'Zigbee2MQTT',
		description: 'FastyBird IoT connector for Zigbee2MQTT devices',
		links: {
			documentation: 'http://www.fastybird.com',
			devDocumentation: 'http://www.fastybird.com',
			bugsTracking: 'http://www.fastybird.com',
		},
		components: {
			connectorDetail: undefined,
			addConnector: undefined,
			editConnector: undefined,
		},
		core: true,
	},
];
