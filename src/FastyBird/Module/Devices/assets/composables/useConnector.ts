import { computed } from 'vue';

import { orderBy } from 'natural-orderby';

import { injectStoresManager } from '@fastybird/miniserver-core';

import {
	channelControlsStoreKey,
	channelPropertiesStoreKey,
	channelsStoreKey,
	connectorControlsStoreKey,
	connectorPropertiesStoreKey,
	connectorsStoreKey,
	deviceControlsStoreKey,
	devicePropertiesStoreKey,
	devicesStoreKey,
} from '../configuration';
import {
	IChannelControl,
	IChannelData,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorData,
	IConnectorProperty,
	IDeviceControl,
	IDeviceData,
	IDeviceProperty,
} from '../types';

import { UseConnector } from './types';

export const useConnector = (id: IConnector['id']): UseConnector => {
	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);
	const connectorControlsStore = storesManager.getStore(connectorControlsStoreKey);
	const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);
	const devicesStore = storesManager.getStore(devicesStoreKey);
	const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
	const channelsStore = storesManager.getStore(channelsStoreKey);
	const channelControlsStore = storesManager.getStore(channelControlsStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const connector = computed<IConnector | null>((): IConnector | null => {
		if (id === null) {
			return null;
		}

		return connectorsStore.findById(id);
	});

	const connectorData = computed<IConnectorData | null>((): IConnectorData | null => {
		if (id === null) {
			return null;
		}

		const connector = connectorsStore.findById(id);

		if (connector === null) {
			return null;
		}

		return {
			connector,
			controls: orderBy<IConnectorControl>(
				connectorControlsStore.findForConnector(connector.id).filter((control) => !control.draft),
				[(v): string => v.name],
				['asc']
			),
			properties: orderBy<IConnectorProperty>(
				connectorPropertiesStore.findForConnector(connector.id),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			),
			devices: orderBy<IDeviceData>(
				devicesStore
					.findForConnector(connector.id)
					.filter((device) => !device.draft)
					.map((device): IDeviceData => {
						return {
							connector,
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
					}),
				[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
				['asc']
			),
		};
	});

	const fetchConnector = async (): Promise<void> => {
		const item = await connectorsStore.findById(id);

		if (item?.draft) {
			return;
		}

		await connectorsStore.get({ id, refresh: !connectorsStore.firstLoadFinished() });

		const connector = connectorsStore.findById(id);

		if (connector) {
			await connectorPropertiesStore.fetch({ connector, refresh: !connectorPropertiesStore.firstLoadFinished(connector.id) });
			await connectorControlsStore.fetch({ connector, refresh: !connectorControlsStore.firstLoadFinished(connector.id) });
		}
	};

	const isLoading = computed<boolean>((): boolean => {
		if (connectorsStore.getting(id)) {
			return true;
		}

		if (connectorsStore.findById(id)) {
			return false;
		}

		return connectorsStore.fetching();
	});

	return {
		connector,
		connectorData,
		isLoading,
		fetchConnector,
	};
};
