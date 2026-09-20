/* eslint-disable @typescript-eslint/no-empty-object-type */
import { ComponentOptionsMixin, DefineComponent } from 'vue';
import { I18n } from 'vue-i18n';
import { Router } from 'vue-router';

import { Pinia } from 'pinia';

import { Client } from '@fastybird/miniserver-core';

import {
	IChannelDetailProps,
	IConnectorDetailProps,
	IConnectorDevicesProps,
	IDeviceChannelsProps,
	IDeviceDetailProps,
	IEditChannelProps,
	IEditConnectorProps,
	IEditDeviceProps,
	connectorDevicesEmits,
	deviceChannelsEmits,
	editChannelEmits,
	editConnectorEmits,
	editDeviceEmits,
} from './components';

export interface IConnectorOptions {
	router?: Router;
	store: Pinia;
	wsClient?: Client;
	i18n?: I18n;
}

export interface IConnectorPlugin {
	type: string;
	source: string;
	name: string;
	description: string;
	icon?: {
		small?: string;
		large?: string;
	};
	links: {
		documentation: string;
		devDocumentation: string;
		bugsTracking: string;
	};
	components: {
		connectorDetail?: DefineComponent<IConnectorDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		connectorDevices?: DefineComponent<
			IConnectorDevicesProps,
			{},
			{},
			{},
			{},
			ComponentOptionsMixin,
			ComponentOptionsMixin,
			typeof connectorDevicesEmits
		>;
		addConnector?: DefineComponent<IEditConnectorProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editConnectorEmits>;
		editConnector?: DefineComponent<IEditConnectorProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editConnectorEmits>;
		deviceDetail?: DefineComponent<IDeviceDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		deviceChannels?: DefineComponent<IDeviceChannelsProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof deviceChannelsEmits>;
		addDevice?: DefineComponent<IEditDeviceProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editDeviceEmits>;
		editDevice?: DefineComponent<IEditDeviceProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editDeviceEmits>;
		channelDetail?: DefineComponent<IChannelDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		addChannel?: DefineComponent<IEditChannelProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editChannelEmits>;
		editChannel?: DefineComponent<IEditChannelProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editChannelEmits>;
	};
	core: boolean;
}
