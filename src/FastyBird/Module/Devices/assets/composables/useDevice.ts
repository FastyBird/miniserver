import { computed } from 'vue';

import { orderBy } from 'natural-orderby';

import { injectStoresManager } from '@fastybird/miniserver-core';

import {
	channelControlsStoreKey,
	channelPropertiesStoreKey,
	channelsStoreKey,
	connectorsStoreKey,
	deviceControlsStoreKey,
	devicePropertiesStoreKey,
	devicesStoreKey,
} from '../configuration';
import { IChannelControl, IChannelData, IChannelProperty, IDevice, IDeviceControl, IDeviceData, IDeviceProperty } from '../types';

import { UseDevice } from './types';

export const useDevice = (id: IDevice['id']): UseDevice => {
	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);
	const devicesStore = storesManager.getStore(devicesStoreKey);
	const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
	const channelsStore = storesManager.getStore(channelsStoreKey);
	const channelControlsStore = storesManager.getStore(channelControlsStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const device = computed<IDevice | null>((): IDevice | null => {
		if (id === null) {
			return null;
		}

		return devicesStore.findById(id);
	});

	const deviceData = computed<IDeviceData | null>((): IDeviceData | null => {
		if (id === null) {
			return null;
		}

		const device = devicesStore.findById(id);

		if (device === null) {
			return null;
		}

		return {
			connector: connectorsStore.findById(device.connector.id),
			device,
			controls: orderBy<IDeviceControl>(
				deviceControlsStore.findForDevice(device.id).filter((control) => !control.draft),
				[(v): string => v.name],
				['asc']
			),
			properties: orderBy<IDeviceProperty>(
				devicePropertiesStore.findForDevice(device.id).filter((property) => !property.draft),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			),
			channels: orderBy<IChannelData>(
				channelsStore.findForDevice(device.id).map((channel): IChannelData => {
					return {
						device,
						channel,
						controls: orderBy<IChannelControl>(
							channelControlsStore.findForChannel(channel.id).filter((control) => !control.draft),
							[(v): string => v.name],
							['asc']
						),
						properties: orderBy<IChannelProperty>(
							channelPropertiesStore.findForChannel(channel.id).filter((property) => !property.draft),
							[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
							['asc']
						),
					};
				}),
				[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
				['asc']
			),
		};
	});

	const fetchDevice = async (): Promise<void> => {
		const item = await devicesStore.findById(id);

		if (item?.draft) {
			return;
		}

		await devicesStore.get({ id, refresh: !devicesStore.firstLoadFinished() });

		const device = devicesStore.findById(id);

		if (device) {
			await devicePropertiesStore.fetch({ device, refresh: !devicePropertiesStore.firstLoadFinished(device.id) });
			await deviceControlsStore.fetch({ device, refresh: !deviceControlsStore.firstLoadFinished(device.id) });
		}
	};

	const isLoading = computed<boolean>((): boolean => {
		if (devicesStore.getting(id)) {
			return true;
		}

		if (devicesStore.findById(id)) {
			return false;
		}

		return devicesStore.fetching();
	});

	return {
		device,
		deviceData,
		isLoading,
		fetchDevice,
	};
};
