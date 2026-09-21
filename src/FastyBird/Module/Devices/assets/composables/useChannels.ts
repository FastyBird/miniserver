import { computed } from 'vue';

import { orderBy } from 'natural-orderby';

import { injectStoresManager } from '@fastybird/miniserver-core';

import { channelControlsStoreKey, channelPropertiesStoreKey, channelsStoreKey, devicesStoreKey } from '../configuration';
import { IChannel, IChannelControl, IChannelData, IChannelProperty, IDevice, UseChannels } from '../types';

export const useChannels = (deviceId?: IDevice['id'] | undefined): UseChannels => {
	const storesManager = injectStoresManager();

	const devicesStore = storesManager.getStore(devicesStoreKey);
	const channelsStore = storesManager.getStore(channelsStoreKey);
	const channelControlsStore = storesManager.getStore(channelControlsStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const channels = computed<IChannel[]>((): IChannel[] => {
		return orderBy<IChannel>(
			channelsStore
				.findAll()
				.filter((channel) => !channel.draft)
				.filter((channel) => {
					return typeof deviceId === 'undefined' || channel.device.id === deviceId;
				}),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		);
	});

	const channelsData = computed<IChannelData[]>((): IChannelData[] => {
		const channels = orderBy<IChannel>(
			channelsStore
				.findAll()
				.filter((channel) => !channel.draft)
				.filter((channel) => {
					return typeof deviceId === 'undefined' || channel.device.id === deviceId;
				}),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		);

		return channels.map((channel) => ({
			device: devicesStore.findById(channel.device.id),
			channel,
			controls: orderBy<IChannelControl>(
				channelControlsStore.findForChannel(channel.id).filter((control) => !control.draft),
				[(v): string => v.name],
				['asc']
			),
			properties: orderBy<IChannelProperty>(
				channelPropertiesStore.findForChannel(channel.id).filter((control) => !control.draft),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			),
		}));
	});

	const fetchChannels = async (overrideChannelId?: IDevice['id']): Promise<void> => {
		const useDeviceId = overrideChannelId ?? deviceId;

		await channelsStore.fetch({ deviceId: useDeviceId, refresh: !channelsStore.firstLoadFinished(useDeviceId) });

		const channels = (typeof useDeviceId !== 'undefined' ? channelsStore.findForDevice(useDeviceId) : channelsStore.findAll()).filter(
			(channel) => !channel.draft
		);

		for (const channel of channels) {
			await channelPropertiesStore.fetch({ channel, refresh: !channelPropertiesStore.firstLoadFinished(channel.id) });
			await channelControlsStore.fetch({ channel, refresh: !channelControlsStore.firstLoadFinished(channel.id) });
		}
	};

	const areLoading = computed<boolean>((): boolean => channelsStore.fetching());

	return {
		channels,
		channelsData,
		areLoading,
		fetchChannels,
	};
};
