import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosError } from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/metadata-library';
import { IStoresManager, injectStoresManager } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/websockets-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.control.json';
import { devicesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ActionRoutes,
	DeviceControlDocument,
	DeviceControlsStoreSetup,
	ExchangeCommand,
	IDevice,
	IDeviceControlsStateSemaphore,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_DEVICES_CONTROLS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IDeviceControl,
	IDeviceControlDatabaseRecord,
	IDeviceControlMeta,
	IDeviceControlRecordFactoryPayload,
	IDeviceControlResponseJson,
	IDeviceControlResponseModel,
	IDeviceControlsActions,
	IDeviceControlsAddActionPayload,
	IDeviceControlsFetchActionPayload,
	IDeviceControlsGetActionPayload,
	IDeviceControlsInsertDataActionPayload,
	IDeviceControlsLoadAllRecordsActionPayload,
	IDeviceControlsLoadRecordActionPayload,
	IDeviceControlsRemoveActionPayload,
	IDeviceControlsResponseJson,
	IDeviceControlsSaveActionPayload,
	IDeviceControlsSetActionPayload,
	IDeviceControlsSocketDataActionPayload,
	IDeviceControlsState,
	IDeviceControlsTransmitCommandActionPayload,
	IDeviceControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IDeviceControlRecordFactoryPayload): Promise<IDeviceControl> => {
	const devicesStore = storesManager.getStore(devicesStoreKey);

	let device = 'device' in data ? lodashGet(data, 'device', null) : null;

	let deviceMeta = data.deviceId ? devicesStore.findMeta(data.deviceId) : null;

	if (device === null && deviceMeta !== null) {
		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	if (device === null) {
		if (!('deviceId' in data)) {
			throw new Error("Device for control couldn't be loaded from store");
		}

		if (!(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for control couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for control couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	return {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		name: data.name,

		// Relations
		relationshipNames: ['device'],

		device: {
			id: device.id,
			type: device.type,
		},
	} as IDeviceControl;
};

const databaseRecordFactory = (record: IDeviceControl): IDeviceControlDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
			parent: record.type.parent,
		},

		name: record.name,

		relationshipNames: record.relationshipNames.map((name) => name),

		device: {
			id: record.device.id,
			type: {
				type: record.device.type.type,
				source: record.device.type.source,
				entity: record.device.type.entity,
			},
		},
	};
};

export const useDeviceControls = defineStore<'devices_module_devices_controls', DeviceControlsStoreSetup>(
	'devices_module_devices_controls',
	(): DeviceControlsStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IDeviceControlsStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IDevice['id'][]>([]);

		const data = ref<{ [key: IDeviceControl['id']]: IDeviceControl } | undefined>(undefined);

		const meta = ref<{ [key: IDeviceControl['id']]: IDeviceControlMeta }>({});

		const firstLoadFinished = (deviceId: IDevice['id']): boolean => firstLoad.value.includes(deviceId);

		const getting = (id: IDeviceControl['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (deviceId: IDevice['id'] | null): boolean =>
			deviceId !== null ? semaphore.value.fetching.items.includes(deviceId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IDeviceControl['id']): IDeviceControl | null => {
			const control: IDeviceControl | undefined = Object.values(data.value ?? {}).find((control: IDeviceControl): boolean => control.id === id);

			return control ?? null;
		};

		const findByName = (device: IDevice, name: IDeviceControl['name']): IDeviceControl | null => {
			const control: IDeviceControl | undefined = Object.values(data.value ?? {}).find((control: IDeviceControl): boolean => {
				return control.device.id === device.id && control.name.toLowerCase() === name.toLowerCase();
			});

			return control ?? null;
		};

		const findForDevice = (deviceId: IDevice['id']): IDeviceControl[] =>
			Object.values(data.value ?? {}).filter((control: IDeviceControl): boolean => control.device.id === deviceId);

		const findMeta = (id: IDeviceControl['id']): IDeviceControlMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IDeviceControlsSetActionPayload): Promise<IDeviceControl> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IDeviceControlsUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.device !== undefined) {
				const items = findForDevice(payload.device.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_DEVICES_CONTROLS);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_DEVICES_CONTROLS);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least device or control id');
		};

		const get = async (payload: IDeviceControlsGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const controlResponse = await axios.get<IDeviceControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/controls/${payload.id}`
				);

				const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IDeviceControlResponseModel;

				data.value = data.value ?? {};
				data.value[controlResponseModel.id] = await storeRecordFactory(storesManager, {
					...controlResponseModel,
					...{ deviceId: controlResponseModel.device.id },
				});

				await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(data.value[controlResponseModel.id]), DB_TABLE_DEVICES_CONTROLS);

				meta.value[controlResponseModel.id] = controlResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.device-controls.get.failed', e, 'Fetching control failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IDeviceControlsFetchActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.items.includes(payload.device.id)) {
				return false;
			}

			const fromDatabase = await loadAllRecords({ device: payload.device });

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items.push(payload.device.id);
			}

			firstLoad.value = firstLoad.value.filter((item) => item !== payload.device.id);
			firstLoad.value = [...new Set(firstLoad.value)];

			try {
				const controlsResponse = await axios.get<IDeviceControlsResponseJson>(`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/controls`);

				const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IDeviceControlResponseModel[];

				for (const control of controlsResponseModel) {
					data.value = data.value ?? {};
					data.value[control.id] = await storeRecordFactory(storesManager, {
						...control,
						...{ deviceId: control.device.id },
					});

					await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(data.value[control.id]), DB_TABLE_DEVICES_CONTROLS);

					meta.value[control.id] = control.type;
				}

				firstLoad.value.push(payload.device.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IDeviceControlDatabaseRecord>(DB_TABLE_DEVICES_CONTROLS);
				const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.device.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_DEVICES_CONTROLS);

					delete meta.value[id];
				}
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					try {
						const devicesStore = storesManager.getStore(devicesStoreKey);

						await devicesStore.get({
							id: payload.device.id,
						});
					} catch (e: any) {
						if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
							const devicesStore = storesManager.getStore(devicesStoreKey);

							devicesStore.unset({
								id: payload.device.id,
							});

							return true;
						}
					}
				}

				throw new ApiError('devices-module.device-controls.fetch.failed', e, 'Fetching controls failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.device.id);
			}

			return true;
		};

		const add = async (payload: IDeviceControlsAddActionPayload): Promise<IDeviceControl> => {
			const newControl = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					deviceId: payload.device.id,
				},
				...payload.data,
			});

			semaphore.value.creating.push(newControl.id);

			data.value = data.value ?? {};
			data.value[newControl.id] = newControl;

			if (newControl.draft) {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);

				return newControl;
			} else {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(payload.device.id);

				if (device === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);

					throw new Error('devices-module.device-controls.get.failed');
				}

				try {
					const createdControl = await axios.post<IDeviceControlResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: newControl,
						})
					);

					const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IDeviceControlResponseModel;

					data.value[createdControlModel.id] = await storeRecordFactory(storesManager, {
						...createdControlModel,
						...{ deviceId: createdControlModel.device.id },
					});

					await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(data.value[createdControlModel.id]), DB_TABLE_DEVICES_CONTROLS);

					meta.value[createdControlModel.id] = createdControlModel.type;

					return data.value[createdControlModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete data.value[newControl.id];

					throw new ApiError('devices-module.device-controls.create.failed', e, 'Create new control failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);
				}
			}
		};

		const save = async (payload: IDeviceControlsSaveActionPayload): Promise<IDeviceControl> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.device-controls.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-controls.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const devicesStore = storesManager.getStore(devicesStoreKey);

			const device = devicesStore.findById(recordToSave.device.id);

			if (device === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.device-controls.get.failed');
			}

			try {
				const savedControl = await axios.post<IDeviceControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${recordToSave.device.id}/controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IDeviceControlResponseModel;

				data.value[savedControlModel.id] = await storeRecordFactory(storesManager, {
					...savedControlModel,
					...{ deviceId: savedControlModel.device.id },
				});

				await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(data.value[savedControlModel.id]), DB_TABLE_DEVICES_CONTROLS);

				meta.value[savedControlModel.id] = savedControlModel.type;

				return data.value[savedControlModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.device-controls.save.failed', e, 'Save draft control failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const remove = async (payload: IDeviceControlsRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.device-controls.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-controls.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const devicesStore = storesManager.getStore(devicesStoreKey);

			const device = devicesStore.findById(recordToDelete.device.id);

			if (device === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.device-controls.get.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_DEVICES_CONTROLS);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/devices/${recordToDelete.device.id}/controls/${recordToDelete.id}`);
				} catch (e: any) {
					const devicesStore = storesManager.getStore(devicesStoreKey);

					const device = devicesStore.findById(recordToDelete.device.id);

					if (device !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ device, id: payload.id });
					}

					throw new ApiError('devices-module.device-controls.delete.failed', e, 'Delete control failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const transmitCommand = async (payload: IDeviceControlsTransmitCommandActionPayload): Promise<boolean> => {
			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-controls.transmit.failed');
			}

			const control = data.value[payload.id];

			const devicesStore = storesManager.getStore(devicesStoreKey);

			const device = devicesStore.findById(control.device.id);

			if (device === null) {
				throw new Error('devices-module.device-controls.transmit.failed');
			}

			const { call } = useWampV1Client<{ data: string }>();

			try {
				const response = await call('', {
					routing_key: ActionRoutes.DEVICE_CONTROL,
					source: control.type.source,
					data: {
						action: ExchangeCommand.SET,
						device: device.id,
						control: control.id,
						expected_value: payload.value,
					},
				});

				if (lodashGet(response.data, 'response') === 'accepted') {
					return true;
				}
			} catch {
				throw new Error('devices-module.device-controls.transmit.failed');
			}

			throw new Error('devices-module.device-controls.transmit.failed');
		};

		const socketData = async (payload: IDeviceControlsSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.DEVICE_CONTROL_DOCUMENT_REPORTED,
					RoutingKeys.DEVICE_CONTROL_DOCUMENT_CREATED,
					RoutingKeys.DEVICE_CONTROL_DOCUMENT_UPDATED,
					RoutingKeys.DEVICE_CONTROL_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: DeviceControlDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<DeviceControlDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.DEVICE_CONTROL_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_DEVICES_CONTROLS);

				delete meta.value[body.id];

				if (data.value && body.id in data.value) {
					delete data.value[body.id];
				}
			} else {
				if (data.value && body.id in data.value) {
					const record = await storeRecordFactory(storesManager, {
						...data.value[body.id],
						...{
							name: body.name,
							deviceId: body.device,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

						meta.value[record.id] = record.type;
					}
				} else {
					const devicesStore = storesManager.getStore(devicesStoreKey);

					const device = devicesStore.findById(body.device);

					if (device !== null) {
						try {
							await get({
								device,
								id: body.id,
							});
						} catch {
							return false;
						}
					}
				}
			}

			return true;
		};

		const insertData = async (payload: IDeviceControlsInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: DeviceControlDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const deviceIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<DeviceControlDocument>(exchangeDocumentSchema);

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
							parent: 'device',
							entity: 'control',
						},
						name: doc.name,
						deviceId: doc.device,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IDeviceControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_CONTROLS);

				meta.value[record.id] = record.type;

				deviceIds.push(doc.device);
			}

			if (documents.length > 1) {
				const uniqueDeviceIds = [...new Set(deviceIds)];

				for (const deviceId of uniqueDeviceIds) {
					firstLoad.value.push(deviceId);
					firstLoad.value = [...new Set(firstLoad.value)];
				}
			}

			return true;
		};

		const loadRecord = async (payload: IDeviceControlsLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IDeviceControlDatabaseRecord>(payload.id, DB_TABLE_DEVICES_CONTROLS);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IDeviceControlsLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IDeviceControlDatabaseRecord>(DB_TABLE_DEVICES_CONTROLS);

			data.value = data.value ?? {};

			for (const record of records) {
				if (payload?.device && payload?.device.id !== record?.device.id) {
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
			findByName,
			findForDevice,
			findMeta,
			set,
			unset,
			get,
			fetch,
			add,
			save,
			remove,
			transmitCommand,
			socketData,
			insertData,
			loadRecord,
			loadAllRecords,
		};
	}
);

export const registerDevicesControlsStore = (pinia: Pinia): Store<string, IDeviceControlsState, object, IDeviceControlsActions> => {
	return useDeviceControls(pinia);
};
