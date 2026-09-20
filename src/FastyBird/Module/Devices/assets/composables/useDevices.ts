import { computed, reactive, ref, watch } from 'vue';

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
import {
	IChannelControl,
	IChannelData,
	IChannelProperty,
	IConnector,
	IDevice,
	IDeviceControl,
	IDeviceData,
	IDeviceProperty,
	UseDevices,
} from '../types';
import { defaultDevicesFilter } from '../utilities';

export const useDevices = (connectorId?: IConnector['id']): UseDevices => {
	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);
	const devicesStore = storesManager.getStore(devicesStoreKey);
	const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
	const channelsStore = storesManager.getStore(channelsStoreKey);
	const channelControlsStore = storesManager.getStore(channelControlsStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const paginateSize = ref<number>(10);

	const paginatePage = ref<number>(1);

	const filters = reactive<UseDevices['filters']>({ ...defaultDevicesFilter });

	const activeFilters = computed<UseDevices['filters']>((): UseDevices['filters'] => filters);

	const sortDir = ref<'asc' | 'desc'>('asc');

	const devices = computed<IDevice[]>((): IDevice[] => {
		return orderBy<IDevice>(
			devicesStore
				.findAll()
				.filter((device) => !device.draft)
				.filter((device) => {
					return typeof connectorId === 'undefined' || device.connector.id === connectorId;
				}),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			[sortDir.value]
		);
	});

	const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
		const devices = orderBy<IDevice>(
			devicesStore
				.findAll()
				.filter((device) => !device.draft)
				.filter((device) => {
					return typeof connectorId === 'undefined' || device.connector.id === connectorId;
				})
				.filter((device) => filters.search === '' || device.title.toLowerCase().includes(filters.search.toLowerCase())),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			[sortDir.value]
		);

		return devices.map((device: IDevice) => {
			return {
				connector: connectorsStore.findById(device.connector.id),
				device,
				controls: orderBy<IDeviceControl>(
					deviceControlsStore.findForDevice(device.id).filter((control) => !control.draft),
					[(v): string => v.name],
					[sortDir.value]
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
	});

	const devicesDataPaginated = computed<IDeviceData[]>((): IDeviceData[] => {
		const start = (paginatePage.value - 1) * paginateSize.value;
		const end = start + paginateSize.value;

		return devicesData.value.slice(start, end);
	});

	const fetchDevices = async (overrideDeviceId?: IConnector['id']): Promise<void> => {
		const useConnectorId = overrideDeviceId ?? connectorId;

		await devicesStore.fetch({ connectorId: useConnectorId, refresh: !devicesStore.firstLoadFinished(useConnectorId) });

		const devices = (typeof useConnectorId !== 'undefined' ? devicesStore.findForConnector(useConnectorId) : devicesStore.findAll()).filter(
			(device) => !device.draft
		);

		for (const device of devices) {
			await devicePropertiesStore.fetch({ device, refresh: !devicePropertiesStore.firstLoadFinished(device.id) });
			await deviceControlsStore.fetch({ device, refresh: !deviceControlsStore.firstLoadFinished(device.id) });
		}
	};

	const areLoading = computed<boolean>((): boolean => {
		if (devicesStore.fetching(connectorId)) {
			return true;
		}

		if (devicesStore.firstLoadFinished(connectorId)) {
			return false;
		}

		return devicesStore.fetching();
	});

	const loaded = computed<boolean>((): boolean => {
		return devicesStore.firstLoadFinished(connectorId);
	});

	const totalRows = computed<number>(() => devices.value.length);

	const resetFilter = (): void => {
		filters.search = defaultDevicesFilter.search;
		filters.state = defaultDevicesFilter.state;
		filters.states = defaultDevicesFilter.states;
		filters.plugins = defaultDevicesFilter.plugins;
		filters.connectors = defaultDevicesFilter.connectors;
	};

	watch(
		(): UseDevices['filters'] => activeFilters.value,
		(): void => {
			paginatePage.value = 1;
		}
	);

	return {
		devices,
		devicesData,
		devicesDataPaginated,
		totalRows,
		areLoading,
		loaded,
		fetchDevices,
		filters,
		paginateSize,
		paginatePage,
		sortDir,
		resetFilter,
	};
};
