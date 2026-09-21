import { computed } from 'vue';

import { orderBy } from 'natural-orderby';

import { injectStoresManager } from '@fastybird/miniserver-core';

import { channelControlsStoreKey, channelPropertiesStoreKey, channelsStoreKey, devicesStoreKey } from '../configuration';
import { IDevice } from '../entry';
import { IChannel, IChannelControl, IChannelData, IChannelProperty } from '../types';

import { UseChannel } from './types';

export const useChannel = (id: IChannel['id']): UseChannel => {
	const storesManager = injectStoresManager();

	const devicesStore = storesManager.getStore(devicesStoreKey);
	const channelsStore = storesManager.getStore(channelsStoreKey);
	const channelControlsStore = storesManager.getStore(channelControlsStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const channel = computed<IChannel | null>((): IChannel | null => {
		if (id === null) {
			return null;
		}

		return channelsStore.findById(id);
	});

	const channelData = computed<IChannelData | null>((): IChannelData | null => {
		if (id === null) {
			return null;
		}

		const channel = channelsStore.findById(id);

		if (channel === null) {
			return null;
		}

		return {
			device: devicesStore.findById(channel.device.id),
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
	});

	const fetchChannel = async (deviceId?: IDevice['id']): Promise<void> => {
		const item = await channelsStore.findById(id);

		if (item?.draft) {
			return;
		}

		await channelsStore.get({ id, deviceId, refresh: !channelsStore.firstLoadFinished() });

		const channel = channelsStore.findById(id);

		if (channel) {
			await channelPropertiesStore.fetch({ channel, refresh: !channelPropertiesStore.firstLoadFinished(channel.id) });
			await channelControlsStore.fetch({ channel, refresh: !channelControlsStore.firstLoadFinished(channel.id) });
		}
	};

	const isLoading = computed<boolean>((): boolean => {
		if (channelsStore.getting(id)) {
			return true;
		}

		if (channelsStore.findById(id)) {
			return false;
		}

		return channelsStore.fetching();
	});

	return {
		channel,
		channelData,
		isLoading,
		fetchChannel,
	};
};
