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
	IConnectorPlugin,
	IConnectorProperty,
	IDeviceControl,
	IDeviceData,
	IDeviceProperty,
	UseConnectors,
} from '../types';

export const useConnectors = (plugin?: IConnectorPlugin['type'] | undefined): UseConnectors => {
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

	const connectors = computed<IConnector[]>((): IConnector[] => {
		return orderBy<IConnector>(
			connectorsStore
				.findAll()
				.filter((connector) => !connector.draft)
				.filter((connector) => {
					return typeof plugin === 'undefined' || connector.type.type === plugin;
				}),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		);
	});

	const connectorsData = computed<IConnectorData[]>((): IConnectorData[] => {
		const connectors = orderBy<IConnector>(
			connectorsStore
				.findAll()
				.filter((connector) => !connector.draft)
				.filter((connector) => {
					return typeof plugin === 'undefined' || connector.type.type === plugin;
				}),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		);

		return connectors.map((connector) => ({
			connector,
			controls: orderBy<IConnectorControl>(
				connectorControlsStore.findForConnector(connector.id).filter((control) => !control.draft),
				[(v): string => v.name],
				['asc']
			),
			properties: orderBy<IConnectorProperty>(
				connectorPropertiesStore.findForConnector(connector.id).filter((control) => !control.draft),
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
		}));
	});

	const fetchConnectors = async (): Promise<void> => {
		await connectorsStore.fetch({ refresh: !connectorsStore.firstLoadFinished() });

		const connectors = connectorsStore.findAll().filter((connector) => !connector.draft);

		for (const connector of connectors) {
			await connectorPropertiesStore.fetch({ connector, refresh: !connectorPropertiesStore.firstLoadFinished(connector.id) });
			await connectorControlsStore.fetch({ connector, refresh: !connectorControlsStore.firstLoadFinished(connector.id) });
		}
	};

	const areLoading = computed<boolean>((): boolean => connectorsStore.fetching());

	return {
		connectors,
		connectorsData,
		areLoading,
		fetchConnectors,
	};
};
