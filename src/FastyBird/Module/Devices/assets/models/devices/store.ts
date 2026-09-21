import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosError, AxiosResponse } from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.json';
import { channelsStoreKey, connectorsStoreKey, deviceControlsStoreKey, devicePropertiesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	DeviceCategory,
	DeviceDocument,
	DevicePropertyIdentifier,
	DevicesStoreSetup,
	IChannelResponseModel,
	IConnector,
	IDeviceControlResponseModel,
	IDeviceDatabaseRecord,
	IDeviceMeta,
	IDeviceProperty,
	IDevicePropertyResponseModel,
	IDevicesInsertDataActionPayload,
	IDevicesLoadAllRecordsActionPayload,
	IDevicesLoadRecordActionPayload,
	IDevicesStateSemaphore,
	IDevicesUnsetActionPayload,
	IPlainRelation,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_DEVICES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IDevice,
	IDeviceRecordFactoryPayload,
	IDeviceResponseJson,
	IDeviceResponseModel,
	IDevicesActions,
	IDevicesAddActionPayload,
	IDevicesEditActionPayload,
	IDevicesFetchActionPayload,
	IDevicesGetActionPayload,
	IDevicesRemoveActionPayload,
	IDevicesResponseJson,
	IDevicesSaveActionPayload,
	IDevicesSetActionPayload,
	IDevicesSocketDataActionPayload,
	IDevicesState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IDeviceRecordFactoryPayload): Promise<IDevice> => {
	const connectorsStore = storesManager.getStore(connectorsStoreKey);

	let connector = 'connector' in data ? lodashGet(data, 'connector', null) : null;

	let connectorMeta = data.connectorId ? connectorsStore.findMeta(data.connectorId) : null;

	if (connector === null && connectorMeta !== null) {
		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	if (connector === null) {
		if (!('connectorId' in data)) {
			throw new Error("Connector for device couldn't be loaded from store");
		}

		if (!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))) {
			throw new Error("Connector for device couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for device couldn't be loaded from store");
		}

		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	const record: IDevice = {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: lodashGet(data, 'name', null),
		comment: lodashGet(data, 'comment', null),

		relationshipNames: ['channels', 'properties', 'controls', 'connector', 'parents', 'children'],

		connector: {
			id: connector.id,
			type: connector.type,
		},

		parents: [],
		children: [],

		channels: [],
		controls: [],
		properties: [],

		owner: lodashGet(data, 'owner', null),

		get stateProperty(): IDeviceProperty | null {
			const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

			const stateRegex = new RegExp(`^${DevicePropertyIdentifier.STATE}_([0-9]+)$`);

			const stateProperty = devicePropertiesStore
				.findForDevice(this.id)
				.find((property) => property.identifier === DevicePropertyIdentifier.STATE || stateRegex.test(property.identifier));

			return stateProperty ?? null;
		},

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},

		get title(): string {
			return this.name ?? this.identifier.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (
			relationName === 'channels' ||
			relationName === 'properties' ||
			relationName === 'controls' ||
			relationName === 'parents' ||
			relationName === 'children'
		) {
			lodashGet(data, relationName, []).forEach((relation: any): void => {
				if (lodashGet(relation, 'id', null) !== null && lodashGet(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: lodashGet(relation, 'id', null),
						type: lodashGet(relation, 'type', null),
					});
				}
			});
		}
	});

	return record;
};

const databaseRecordFactory = (record: IDevice): IDeviceDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
		},

		category: record.category,
		identifier: record.identifier,
		name: record.name,
		comment: record.comment,

		relationshipNames: record.relationshipNames.map((name) => name),

		parents: record.parents.map((parent) => ({
			id: parent.id,
			type: { type: parent.type.type, source: parent.type.source, entity: parent.type.entity },
		})),
		children: record.children.map((children) => ({
			id: children.id,
			type: { type: children.type.type, source: children.type.source, entity: children.type.entity },
		})),

		channels: record.channels.map((channel) => ({
			id: channel.id,
			type: { type: channel.type.type, source: channel.type.source, entity: channel.type.entity },
		})),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
		})),

		connector: {
			id: record.connector.id,
			type: {
				type: record.connector.type.type,
				source: record.connector.type.source,
				entity: record.connector.type.entity,
			},
		},

		owner: record.owner,
	};
};

const addChannelsRelations = async (
	storesManager: IStoresManager,
	device: IDevice,
	channels: (IChannelResponseModel | IPlainRelation)[]
): Promise<void> => {
	const channelsStore = storesManager.getStore(channelsStoreKey);

	for (const channel of channels) {
		if ('identifier' in channel) {
			await channelsStore.set({
				data: {
					...channel,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

const addPropertiesRelations = async (
	storesManager: IStoresManager,
	device: IDevice,
	properties: (IDevicePropertyResponseModel | IPlainRelation)[]
): Promise<void> => {
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

	for (const property of properties) {
		if ('identifier' in property) {
			await devicePropertiesStore.set({
				data: {
					...property,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (
	storesManager: IStoresManager,
	device: IDevice,
	controls: (IDeviceControlResponseModel | IPlainRelation)[]
): Promise<void> => {
	const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);

	for (const control of controls) {
		if ('identifier' in control) {
			await deviceControlsStore.set({
				data: {
					...control,
					...{
						deviceId: device.id,
					},
				},
			});
		}
	}
};

export const useDevices = defineStore<'devices_module_devices', DevicesStoreSetup>('devices_module_devices', (): DevicesStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IDevicesStateSemaphore>({
		fetching: {
			items: [],
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<IConnector['id'][]>([]);

	const data = ref<{ [key: IDevice['id']]: IDevice } | undefined>(undefined);

	const meta = ref<{ [key: IDevice['id']]: IDeviceMeta }>({});

	const firstLoadFinished = (connectorId?: IConnector['id'] | null): boolean =>
		connectorId !== null && typeof connectorId !== 'undefined' ? firstLoad.value.includes(connectorId) : firstLoad.value.includes('all');

	const getting = (id: IDevice['id']): boolean => semaphore.value.fetching.item.includes(id);

	const fetching = (connectorId?: IConnector['id'] | null): boolean =>
		connectorId !== null && typeof connectorId !== 'undefined'
			? semaphore.value.fetching.items.includes(connectorId)
			: semaphore.value.fetching.items.includes('all');

	const findById = (id: IDevice['id']): IDevice | null => (id in (data.value ?? {}) ? (data.value ?? {})[id] : null);

	const findForConnector = (connectorId: IConnector['id']): IDevice[] =>
		Object.values(data.value ?? {}).filter((device: IDevice): boolean => device.connector.id === connectorId);

	const findAll = (): IDevice[] => Object.values(data.value ?? {});

	const findMeta = (id: IDevice['id']): IDeviceMeta | null => (id in meta.value ? meta.value[id] : null);

	const set = async (payload: IDevicesSetActionPayload): Promise<IDevice> => {
		const record = await storeRecordFactory(storesManager, payload.data);

		if ('channels' in payload.data && Array.isArray(payload.data.channels)) {
			await addChannelsRelations(storesManager, record, payload.data.channels);
		}

		if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
			await addPropertiesRelations(storesManager, record, payload.data.properties);
		}

		if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
			await addControlsRelations(storesManager, record, payload.data.controls);
		}

		await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

		meta.value[record.id] = record.type;

		data.value = data.value ?? {};
		return (data.value[record.id] = record);
	};

	const unset = async (payload: IDevicesUnsetActionPayload): Promise<void> => {
		if (!data.value) {
			return;
		}

		if (payload.connector !== undefined) {
			const items = findForConnector(payload.connector.id);

			for (const item of items) {
				if (item.id in (data.value ?? {})) {
					await removeRecord(item.id, DB_TABLE_DEVICES);

					delete meta.value[item.id];

					delete (data.value ?? {})[item.id];
				}
			}

			return;
		} else if (payload.id !== undefined) {
			await removeRecord(payload.id, DB_TABLE_DEVICES);

			delete meta.value[payload.id];

			delete data.value[payload.id];

			return;
		}

		throw new Error('You have to provide at least connector or device id');
	};

	const get = async (payload: IDevicesGetActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.item.includes(payload.id)) {
			return false;
		}

		const fromDatabase = await loadRecord({ id: payload.id });

		if (fromDatabase && payload.refresh === false) {
			return true;
		}

		if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
			semaphore.value.fetching.item.push(payload.id);
		}

		try {
			let deviceResponse: AxiosResponse<IDeviceResponseJson>;

			if (payload.connectorId) {
				deviceResponse = await axios.get<IDeviceResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connectorId}/devices/${payload.id}`);
			} else {
				deviceResponse = await axios.get<IDeviceResponseJson>(`/${ModulePrefix.DEVICES}/v1/devices/${payload.id}`);
			}

			const deviceResponseModel = jsonApiFormatter.deserialize(deviceResponse.data) as IDeviceResponseModel;

			data.value = data.value ?? {};
			data.value[deviceResponseModel.id] = await storeRecordFactory(storesManager, {
				...deviceResponseModel,
				...{ connectorId: deviceResponseModel.connector.id },
			});

			await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(data.value[deviceResponseModel.id]), DB_TABLE_DEVICES);

			meta.value[deviceResponseModel.id] = deviceResponseModel.type;
		} catch (e: any) {
			if (e instanceof AxiosError && e.status === 404) {
				await unset({
					id: payload.id,
				});

				return true;
			}

			throw new ApiError('devices-module.devices.get.failed', e, 'Fetching device failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const fetch = async (payload?: IDevicesFetchActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.items.includes(payload?.connectorId ?? 'all')) {
			return false;
		}

		const fromDatabase = await loadAllRecords({ connectorId: payload?.connectorId });

		if (fromDatabase && payload?.refresh === false) {
			return true;
		}

		if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
			semaphore.value.fetching.items.push(payload?.connectorId ?? 'all');
		}

		firstLoad.value = firstLoad.value.filter((item) => item !== (payload?.connectorId ?? 'all'));
		firstLoad.value = [...new Set(firstLoad.value)];

		const connectorIds: string[] = [];

		try {
			let devicesResponse: AxiosResponse<IDevicesResponseJson>;

			if (payload?.connectorId) {
				devicesResponse = await axios.get<IDevicesResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connectorId}/devices`);
			} else {
				devicesResponse = await axios.get<IDevicesResponseJson>(`/${ModulePrefix.DEVICES}/v1/devices`);
			}

			const devicesResponseModel = jsonApiFormatter.deserialize(devicesResponse.data) as IDeviceResponseModel[];

			for (const device of devicesResponseModel) {
				data.value = data.value ?? {};
				data.value[device.id] = await storeRecordFactory(storesManager, {
					...device,
					...{ connectorId: device.connector.id },
				});

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(data.value[device.id]), DB_TABLE_DEVICES);

				meta.value[device.id] = device.type;

				connectorIds.push(device.connector.id);
			}

			if (payload && payload.connectorId) {
				firstLoad.value.push(payload.connectorId);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);
				const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connectorId).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_DEVICES);

					delete meta.value[id];
				}
			} else {
				firstLoad.value.push('all');
				firstLoad.value = [...new Set(firstLoad.value)];

				const uniqueConnectorIds = [...new Set(connectorIds)];

				for (const connectorId of uniqueConnectorIds) {
					firstLoad.value.push(connectorId);
					firstLoad.value = [...new Set(firstLoad.value)];
				}

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_DEVICES);

					delete meta.value[id];
				}
			}
		} catch (e: any) {
			if (e instanceof AxiosError && e.status === 404 && typeof payload?.connectorId !== 'undefined') {
				try {
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					await connectorsStore.get({
						id: payload?.connectorId,
					});
				} catch (e: any) {
					if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
						const connectorsStore = storesManager.getStore(connectorsStoreKey);

						connectorsStore.unset({
							id: payload?.connectorId,
						});

						return true;
					}
				}
			}

			throw new ApiError('devices-module.devices.fetch.failed', e, 'Fetching devices failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== (payload?.connectorId ?? 'all'));
			}
		}

		return true;
	};

	const add = async (payload: IDevicesAddActionPayload): Promise<IDevice> => {
		const newDevice = await storeRecordFactory(storesManager, {
			...{
				id: payload?.id,
				type: payload?.type,
				category: DeviceCategory.GENERIC,
				draft: payload?.draft,
				connectorId: payload.connector.id,
				parents: payload.parents,
			},
			...payload.data,
		});

		semaphore.value.creating.push(newDevice.id);

		data.value = data.value ?? {};
		data.value[newDevice.id] = newDevice;

		if (newDevice.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newDevice.id);

			return newDevice;
		} else {
			try {
				const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

				const properties = devicePropertiesStore.findForDevice(newDevice.id);

				const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);

				const controls = deviceControlsStore.findForDevice(newDevice.id);

				const createdDevice = await axios.post<IDeviceResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices`,
					jsonApiFormatter.serialize({
						stuff: {
							...newDevice,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const createdDeviceModel = jsonApiFormatter.deserialize(createdDevice.data) as IDeviceResponseModel;

				data.value[createdDeviceModel.id] = await storeRecordFactory(storesManager, {
					...createdDeviceModel,
					...{ connectorId: createdDeviceModel.connector.id },
				});

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(data.value[createdDeviceModel.id]), DB_TABLE_DEVICES);

				meta.value[createdDeviceModel.id] = createdDeviceModel.type;
			} catch (e: any) {
				// Record could not be created on api, we have to remove it from database
				delete data.value[newDevice.id];

				throw new ApiError('devices-module.devices.create.failed', e, 'Create new device failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newDevice.id);
			}

			return data.value[newDevice.id];
		}
	};

	const edit = async (payload: IDevicesEditActionPayload): Promise<IDevice> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.devices.update.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.devices.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IDevice;

		data.value[payload.id] = await storeRecordFactory(storesManager, {
			...updatedRecord,
		});

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

				const properties = devicePropertiesStore.findForDevice(updatedRecord.id);

				const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);

				const controls = deviceControlsStore.findForDevice(updatedRecord.id);

				const updatedDevice = await axios.patch<IDeviceResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${payload.id}`,
					jsonApiFormatter.serialize({
						stuff: {
							...updatedRecord,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const updatedDeviceModel = jsonApiFormatter.deserialize(updatedDevice.data) as IDeviceResponseModel;

				data.value[updatedDeviceModel.id] = await storeRecordFactory(storesManager, {
					...updatedDeviceModel,
					...{ connectorId: updatedDeviceModel.connector.id },
				});

				await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(data.value[updatedDeviceModel.id]), DB_TABLE_DEVICES);

				meta.value[updatedDeviceModel.id] = updatedDeviceModel.type;
			} catch (e: any) {
				// Updating record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('devices-module.devices.update.failed', e, 'Edit device failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}

			return data.value[payload.id];
		}
	};

	const save = async (payload: IDevicesSaveActionPayload): Promise<IDevice> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.devices.save.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.devices.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

			const properties = devicePropertiesStore.findForDevice(recordToSave.id);

			const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);

			const controls = deviceControlsStore.findForDevice(recordToSave.id);

			const savedDevice = await axios.post<IDeviceResponseJson>(
				`/${ModulePrefix.DEVICES}/v1/devices`,
				jsonApiFormatter.serialize({
					stuff: {
						...recordToSave,
						properties,
						controls,
					},
					includeNames: ['properties', 'controls'],
				})
			);

			const savedDeviceModel = jsonApiFormatter.deserialize(savedDevice.data) as IDeviceResponseModel;

			data.value[savedDeviceModel.id] = await storeRecordFactory(storesManager, {
				...savedDeviceModel,
				...{ connectorId: savedDeviceModel.connector.id },
			});

			await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(data.value[savedDeviceModel.id]), DB_TABLE_DEVICES);

			meta.value[savedDeviceModel.id] = savedDeviceModel.type;
		} catch (e: any) {
			throw new ApiError('devices-module.devices.save.failed', e, 'Save draft device failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}

		return data.value[payload.id];
	};

	const remove = async (payload: IDevicesRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('devices-module.devices.delete.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			return true;
		}

		const channelsStore = storesManager.getStore(channelsStoreKey);
		const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);
		const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		await removeRecord(payload.id, DB_TABLE_DEVICES);

		delete meta.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

			channelsStore.unset({ device: recordToDelete });
			deviceControlsStore.unset({ device: recordToDelete });
			devicePropertiesStore.unset({ device: recordToDelete });
		} else {
			try {
				await axios.delete(`/${ModulePrefix.DEVICES}/v1/devices/${payload.id}`);

				channelsStore.unset({ device: recordToDelete });
				deviceControlsStore.unset({ device: recordToDelete });
				devicePropertiesStore.unset({ device: recordToDelete });
			} catch (e: any) {
				// Deleting record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('devices-module.devices.delete.failed', e, 'Delete device failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const socketData = async (payload: IDevicesSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.DEVICE_DOCUMENT_REPORTED,
				RoutingKeys.DEVICE_DOCUMENT_CREATED,
				RoutingKeys.DEVICE_DOCUMENT_UPDATED,
				RoutingKeys.DEVICE_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: DeviceDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<DeviceDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (payload.routingKey === RoutingKeys.DEVICE_DOCUMENT_DELETED) {
			await removeRecord(body.id, DB_TABLE_DEVICES);

			delete meta.value[body.id];

			if (data.value && body.id in data.value) {
				const recordToDelete = data.value[body.id];

				delete data.value[body.id];

				const channelsStore = storesManager.getStore(channelsStoreKey);
				const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
				const deviceControlsStore = storesManager.getStore(deviceControlsStoreKey);

				channelsStore.unset({ device: recordToDelete });
				devicePropertiesStore.unset({ device: recordToDelete });
				deviceControlsStore.unset({ device: recordToDelete });
			}
		} else {
			if (payload.routingKey === RoutingKeys.DEVICE_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			if (data.value && body.id in data.value) {
				const record = await storeRecordFactory(storesManager, {
					...data.value[body.id],
					...{
						category: body.category,
						name: body.name,
						comment: body.comment,
						connectorId: body.connector,
						owner: body.owner,
					},
				});

				if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
					data.value[body.id] = record;

					await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

					meta.value[record.id] = record.type;
				}
			} else {
				try {
					await get({ id: body.id });
				} catch {
					return false;
				}
			}
		}

		return true;
	};

	const insertData = async (payload: IDevicesInsertDataActionPayload): Promise<boolean> => {
		data.value = data.value ?? {};

		let documents: DeviceDocument[];

		if (Array.isArray(payload.data)) {
			documents = payload.data;
		} else {
			documents = [payload.data];
		}

		const connectorIds = [];

		for (const doc of documents) {
			const isValid = jsonSchemaValidator.compile<DeviceDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(doc)) {
					return false;
				}
			} catch {
				return false;
			}

			const record = await storeRecordFactory(storesManager, {
				...data.value[doc.id],
				...{
					id: doc.id,
					type: {
						type: doc.type,
						source: doc.source,
						entity: 'device',
					},
					category: doc.category,
					identifier: doc.identifier,
					name: doc.name,
					comment: doc.comment,
					connectorId: doc.connector,
					owner: doc.owner,
				},
			});

			if (documents.length === 1) {
				data.value[doc.id] = record;
			}

			await addRecord<IDeviceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES);

			meta.value[record.id] = record.type;

			connectorIds.push(doc.connector);
		}

		if (documents.length > 1) {
			const uniqueConnectorIds = [...new Set(connectorIds)];

			if (uniqueConnectorIds.length > 1) {
				firstLoad.value.push('all');
				firstLoad.value = [...new Set(firstLoad.value)];
			}

			for (const connectorId of uniqueConnectorIds) {
				firstLoad.value.push(connectorId);
				firstLoad.value = [...new Set(firstLoad.value)];
			}
		}

		return true;
	};

	const loadRecord = async (payload: IDevicesLoadRecordActionPayload): Promise<boolean> => {
		const record = await getRecord<IDeviceDatabaseRecord>(payload.id, DB_TABLE_DEVICES);

		if (record) {
			data.value = data.value ?? {};
			data.value[payload.id] = await storeRecordFactory(storesManager, record);

			return true;
		}

		return false;
	};

	const loadAllRecords = async (payload?: IDevicesLoadAllRecordsActionPayload): Promise<boolean> => {
		const records = await getAllRecords<IDeviceDatabaseRecord>(DB_TABLE_DEVICES);

		data.value = data.value ?? {};

		for (const record of records) {
			if (payload?.connectorId && payload?.connectorId !== record?.connector.id) {
				continue;
			}

			data.value[record.id] = await storeRecordFactory(storesManager, record);
		}

		return true;
	};

	return {
		semaphore,
		firstLoad,
		data,
		meta,
		firstLoadFinished,
		getting,
		fetching,
		findById,
		findForConnector,
		findAll,
		findMeta,
		set,
		unset,
		get,
		fetch,
		add,
		edit,
		save,
		remove,
		socketData,
		insertData,
		loadRecord,
		loadAllRecords,
	};
});

export const registerDevicesStore = (pinia: Pinia): Store<string, IDevicesState, object, IDevicesActions> => {
	return useDevices(pinia);
};
