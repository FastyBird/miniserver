import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosError } from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.device.property.json';
import { devicesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	DevicePropertiesStoreSetup,
	DevicePropertyDocument,
	IDevice,
	IDevicePropertiesInsertDataActionPayload,
	IDevicePropertiesLoadAllRecordsActionPayload,
	IDevicePropertiesLoadRecordActionPayload,
	IDevicePropertiesSetStateActionPayload,
	IDevicePropertiesStateSemaphore,
	IDevicePropertyDatabaseRecord,
	IDevicePropertyMeta,
	IPlainRelation,
	PropertyCategory,
	RoutingKeys,
} from '../../types';
import { PropertyType } from '../../types';
import { DB_TABLE_DEVICES_PROPERTIES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IDevicePropertiesActions,
	IDevicePropertiesAddActionPayload,
	IDevicePropertiesEditActionPayload,
	IDevicePropertiesFetchActionPayload,
	IDevicePropertiesGetActionPayload,
	IDevicePropertiesRemoveActionPayload,
	IDevicePropertiesResponseJson,
	IDevicePropertiesSaveActionPayload,
	IDevicePropertiesSetActionPayload,
	IDevicePropertiesSocketDataActionPayload,
	IDevicePropertiesState,
	IDevicePropertiesUnsetActionPayload,
	IDeviceProperty,
	IDevicePropertyRecordFactoryPayload,
	IDevicePropertyResponseJson,
	IDevicePropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IDevicePropertyRecordFactoryPayload): Promise<IDeviceProperty> => {
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
			throw new Error("Device for property couldn't be loaded from store");
		}

		if (devicesStore.findById(data.deviceId as string) === null && !(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for property couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for property couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	const record: IDeviceProperty = {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: lodashGet(data, 'name', null),
		settable: lodashGet(data, 'settable', false),
		queryable: lodashGet(data, 'queryable', false),
		dataType: data.dataType,
		unit: lodashGet(data, 'unit', null),
		format: lodashGet(data, 'format', null),
		invalid: lodashGet(data, 'invalid', null),
		scale: lodashGet(data, 'scale', null),
		step: lodashGet(data, 'step', null),
		default: lodashGet(data, 'default', null),
		valueTransformer: lodashGet(data, 'valueTransformer', null),

		value: lodashGet(data, 'value', null),
		actualValue: lodashGet(data, 'actualValue', null),
		expectedValue: lodashGet(data, 'expectedValue', null),
		pending: lodashGet(data, 'pending', false),
		isValid: lodashGet(data, 'isValid', false),
		command: lodashGet(data, 'command', null),
		lastResult: lodashGet(data, 'lastResult', null),
		backupValue: lodashGet(data, 'backup', null),

		// Relations
		relationshipNames: ['device', 'parent', 'children'],

		device: {
			id: device.id,
			type: device.type,
		},

		parent: null,
		children: [],

		get title(): string {
			return this.name ?? this.identifier.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'children') {
			lodashGet(data, relationName, []).forEach((relation: any): void => {
				if (lodashGet(relation, 'id', null) !== null && lodashGet(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: lodashGet(relation, 'id', null),
						type: lodashGet(relation, 'type', null),
					});
				}
			});
		} else if (relationName === 'parent') {
			const parentId = lodashGet(data, `${relationName}.id`, null);
			const parentType = lodashGet(data, `${relationName}.type`, null);

			if (parentId !== null && parentType !== null) {
				(record[relationName] as IPlainRelation) = {
					id: parentId,
					type: parentType,
				};
			}
		}
	});

	return record;
};

const databaseRecordFactory = (record: IDeviceProperty): IDevicePropertyDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
			parent: record.type.parent,
		},

		category: record.category,
		identifier: record.identifier,
		name: record.name,
		settable: record.settable,
		queryable: record.queryable,
		dataType: record.dataType,
		unit: record.unit,
		format: JSON.parse(JSON.stringify(record.format)),
		invalid: record.invalid,
		scale: record.scale,
		step: record.step,
		valueTransformer: record.valueTransformer,
		default: record.default,

		// Static property
		value: record.value,

		relationshipNames: record.relationshipNames.map((name) => name),

		parent: record.parent
			? {
					id: record.device.id,
					type: {
						type: record.device.type.type,
						source: record.device.type.source,
						entity: record.device.type.entity,
						parent: record.device.type.parent,
					},
				}
			: null,
		children: record.children.map((children) => ({
			id: children.id,
			type: { type: children.type.type, source: children.type.source, entity: children.type.entity, parent: children.type.parent },
		})),

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

export const useDeviceProperties = defineStore<'devices_module_devices_properties', DevicePropertiesStoreSetup>(
	'devices_module_devices_properties',
	(): DevicePropertiesStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IDevicePropertiesStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IDevice['id'][]>([]);

		const data = ref<{ [key: IDeviceProperty['id']]: IDeviceProperty } | undefined>(undefined);

		const meta = ref<{ [key: IDeviceProperty['id']]: IDevicePropertyMeta }>({});

		const firstLoadFinished = (deviceId: IDevice['id']): boolean => firstLoad.value.includes(deviceId);

		const getting = (id: IDeviceProperty['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (deviceId: IDevice['id'] | null): boolean =>
			deviceId !== null ? semaphore.value.fetching.items.includes(deviceId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IDeviceProperty['id']): IDeviceProperty | null => {
			const property: IDeviceProperty | undefined = Object.values(data.value ?? {}).find((property: IDeviceProperty): boolean => property.id === id);

			return property ?? null;
		};

		const findByIdentifier = (device: IDevice, identifier: IDeviceProperty['identifier']): IDeviceProperty | null => {
			const property: IDeviceProperty | undefined = Object.values(data.value ?? {}).find((property: IDeviceProperty): boolean => {
				return property.device.id === device.id && property.identifier.toLowerCase() === identifier.toLowerCase();
			});

			return property ?? null;
		};

		const findForDevice = (deviceId: IDevice['id']): IDeviceProperty[] =>
			Object.values(data.value ?? {}).filter((property: IDeviceProperty): boolean => property.device.id === deviceId);

		const findMeta = (id: IDeviceProperty['id']): IDevicePropertyMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IDevicePropertiesSetActionPayload): Promise<IDeviceProperty> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IDevicePropertiesUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.device !== undefined) {
				const items = findForDevice(payload.device.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_DEVICES_PROPERTIES);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_DEVICES_PROPERTIES);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least device or property id');
		};

		const get = async (payload: IDevicePropertiesGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const propertyResponse = await axios.get<IDevicePropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/properties/${payload.id}`
				);

				const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IDevicePropertyResponseModel;

				data.value = data.value ?? {};
				data.value[propertyResponseModel.id] = await storeRecordFactory(storesManager, {
					...propertyResponseModel,
					...{ deviceId: propertyResponseModel.device.id, parentId: propertyResponseModel.parent?.id },
				});

				await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(data.value[propertyResponseModel.id]), DB_TABLE_DEVICES_PROPERTIES);

				meta.value[propertyResponseModel.id] = propertyResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.device-properties.get.failed', e, 'Fetching property failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IDevicePropertiesFetchActionPayload): Promise<boolean> => {
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
				const propertiesResponse = await axios.get<IDevicePropertiesResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/properties`
				);

				const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IDevicePropertyResponseModel[];

				for (const property of propertiesResponseModel) {
					data.value = data.value ?? {};
					data.value[property.id] = await storeRecordFactory(storesManager, {
						...property,
						...{ deviceId: property.device.id, parentId: property.parent?.id },
					});

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(data.value[property.id]), DB_TABLE_DEVICES_PROPERTIES);

					meta.value[property.id] = property.type;
				}

				firstLoad.value.push(payload.device.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IDevicePropertyDatabaseRecord>(DB_TABLE_DEVICES_PROPERTIES);
				const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.device.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_DEVICES_PROPERTIES);

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

				throw new ApiError('devices-module.device-properties.fetch.failed', e, 'Fetching properties failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.device.id);
			}

			return true;
		};

		const add = async (payload: IDevicePropertiesAddActionPayload): Promise<IDeviceProperty> => {
			const newProperty = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					category: PropertyCategory.GENERIC,
					draft: payload?.draft,
					deviceId: payload.device.id,
				},
				...payload.data,
			});

			semaphore.value.creating.push(newProperty.id);

			data.value = data.value ?? {};
			data.value[newProperty.id] = newProperty;

			if (newProperty.draft) {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);

				return newProperty;
			} else {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(payload.device.id);

				if (device === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);

					throw new Error('devices-module.device-properties.create.failed');
				}

				try {
					const apiData: Partial<IDeviceProperty> =
						newProperty.parent !== null
							? {
									id: newProperty.id,
									type: newProperty.type,
									identifier: newProperty.identifier,
									name: newProperty.name,
									value: newProperty.value,
									device: newProperty.device,
									parent: newProperty.parent,
									relationshipNames: ['device', 'parent'],
								}
							: {
									id: newProperty.id,
									type: newProperty.type,
									identifier: newProperty.identifier,
									name: newProperty.name,
									value: newProperty.value,
									settable: newProperty.settable,
									queryable: newProperty.queryable,
									dataType: newProperty.dataType,
									unit: newProperty.unit,
									invalid: newProperty.invalid,
									scale: newProperty.scale,
									step: newProperty.step,
									format: newProperty.format,
									default: newProperty.default,
									valueTransformer: newProperty.valueTransformer,
									device: newProperty.device,
									relationshipNames: ['device'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const createdProperty = await axios.post<IDevicePropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IDevicePropertyResponseModel;

					data.value[createdPropertyModel.id] = await storeRecordFactory(storesManager, {
						...createdPropertyModel,
						...{ deviceId: createdPropertyModel.device.id, parentId: createdPropertyModel.parent?.id },
					});

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(data.value[createdPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

					meta.value[createdPropertyModel.id] = createdPropertyModel.type;

					return data.value[createdPropertyModel.id];
				} catch (e: any) {
					// Transformer could not be created on api, we have to remove it from database
					delete data.value[newProperty.id];

					throw new ApiError('devices-module.device-properties.create.failed', e, 'Create new property failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);
				}
			}
		};

		const edit = async (payload: IDevicePropertiesEditActionPayload): Promise<IDeviceProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.device-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			const updatedRecord = {
				...existingRecord,
				...payload.data,
				...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
			} as IDeviceProperty;

			data.value[payload.id] = await storeRecordFactory(storesManager, {
				...updatedRecord,
			});

			if (updatedRecord.draft) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				return data.value[payload.id];
			} else {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(updatedRecord.device.id);

				if (device === null) {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.device-properties.update.failed');
				}

				try {
					const apiData: Partial<IDeviceProperty> =
						updatedRecord.parent !== null
							? {
									id: updatedRecord.id,
									type: updatedRecord.type,
									identifier: updatedRecord.identifier,
									name: updatedRecord.name,
									value: updatedRecord.value,
									device: updatedRecord.device,
									parent: updatedRecord.parent,
									relationshipNames: ['device', 'parent'],
								}
							: {
									id: updatedRecord.id,
									type: updatedRecord.type,
									identifier: updatedRecord.identifier,
									name: updatedRecord.name,
									value: updatedRecord.value,
									settable: updatedRecord.settable,
									queryable: updatedRecord.queryable,
									dataType: updatedRecord.dataType,
									unit: updatedRecord.unit,
									invalid: updatedRecord.invalid,
									scale: updatedRecord.scale,
									step: updatedRecord.step,
									format: updatedRecord.format,
									default: updatedRecord.default,
									valueTransformer: updatedRecord.valueTransformer,
									device: updatedRecord.device,
									relationshipNames: ['device'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const updatedProperty = await axios.patch<IDevicePropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/devices/${updatedRecord.device.id}/properties/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IDevicePropertyResponseModel;

					data.value[updatedPropertyModel.id] = await storeRecordFactory(storesManager, {
						...updatedPropertyModel,
						...{ deviceId: updatedPropertyModel.device.id, parentId: updatedPropertyModel.parent?.id },
					});

					await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(data.value[updatedPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

					meta.value[updatedPropertyModel.id] = updatedPropertyModel.type;

					return data.value[updatedPropertyModel.id];
				} catch (e: any) {
					const devicesStore = storesManager.getStore(devicesStoreKey);

					const device = devicesStore.findById(updatedRecord.device.id);

					if (device !== null) {
						// Updating entity on api failed, we need to refresh entity
						await get({ device, id: payload.id });
					}

					throw new ApiError('devices-module.device-properties.update.failed', e, 'Edit property failed.');
				} finally {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
				}
			}
		};

		const save = async (payload: IDevicePropertiesSaveActionPayload): Promise<IDeviceProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.device-properties.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-properties.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const devicesStore = storesManager.getStore(devicesStoreKey);

			const device = devicesStore.findById(recordToSave.device.id);

			if (device === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.device-properties.save.failed');
			}

			try {
				const apiData: Partial<IDeviceProperty> =
					recordToSave.parent !== null
						? {
								id: recordToSave.id,
								type: recordToSave.type,
								identifier: recordToSave.identifier,
								name: recordToSave.name,
								value: recordToSave.value,
								device: recordToSave.device,
								parent: recordToSave.parent,
								relationshipNames: ['device', 'parent'],
							}
						: {
								id: recordToSave.id,
								type: recordToSave.type,
								identifier: recordToSave.identifier,
								name: recordToSave.name,
								value: recordToSave.value,
								settable: recordToSave.settable,
								queryable: recordToSave.queryable,
								dataType: recordToSave.dataType,
								unit: recordToSave.unit,
								invalid: recordToSave.invalid,
								scale: recordToSave.scale,
								step: recordToSave.step,
								format: recordToSave.format,
								default: recordToSave.default,
								valueTransformer: recordToSave.valueTransformer,
								device: recordToSave.device,
								relationshipNames: ['device'],
							};

				if (apiData?.type?.type === PropertyType.DYNAMIC) {
					delete apiData.value;
				}

				if (apiData?.type?.type === PropertyType.VARIABLE) {
					delete apiData.settable;
					delete apiData.queryable;
				}

				const savedProperty = await axios.post<IDevicePropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${recordToSave.device.id}/properties`,
					jsonApiFormatter.serialize({
						stuff: apiData,
					})
				);

				const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IDevicePropertyResponseModel;

				data.value[savedPropertyModel.id] = await storeRecordFactory(storesManager, {
					...savedPropertyModel,
					...{ deviceId: savedPropertyModel.device.id, parentId: savedPropertyModel.parent?.id },
				});

				await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(data.value[savedPropertyModel.id]), DB_TABLE_DEVICES_PROPERTIES);

				meta.value[savedPropertyModel.id] = savedPropertyModel.type;

				return data.value[savedPropertyModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.device-properties.save.failed', e, 'Save draft property failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const setState = async (payload: IDevicePropertiesSetStateActionPayload): Promise<IDeviceProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.device-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			data.value[payload.id] = {
				...existingRecord,
				...payload.data,
				...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
			} as IDeviceProperty;

			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		};

		const remove = async (payload: IDevicePropertiesRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.device-properties.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.device-properties.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const devicesStore = storesManager.getStore(devicesStoreKey);

			const device = devicesStore.findById(recordToDelete.device.id);

			if (device === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.device-properties.delete.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_DEVICES_PROPERTIES);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/devices/${recordToDelete.device.id}/properties/${recordToDelete.id}`);
				} catch (e: any) {
					const devicesStore = storesManager.getStore(devicesStoreKey);

					const device = devicesStore.findById(recordToDelete.device.id);

					if (device !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ device, id: payload.id });
					}

					throw new ApiError('devices-module.device-properties.delete.failed', e, 'Delete property failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const socketData = async (payload: IDevicePropertiesSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.DEVICE_PROPERTY_DOCUMENT_REPORTED,
					RoutingKeys.DEVICE_PROPERTY_DOCUMENT_CREATED,
					RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED,
					RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: DevicePropertyDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<DevicePropertyDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_DEVICES_PROPERTIES);

				delete meta.value[body.id];

				if (data.value && body.id in data.value) {
					delete data.value[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.DEVICE_PROPERTY_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
					return true;
				}

				if (data.value && body.id in data.value) {
					const record = await storeRecordFactory(storesManager, {
						...JSON.parse(JSON.stringify(data.value[body.id])),
						...{
							category: body.category,
							name: body.name,
							settable: body.settable,
							queryable: body.queryable,
							dataType: body.data_type,
							unit: body.unit,
							format: body.format,
							invalid: body.invalid,
							scale: body.scale,
							step: body.step,
							default: body.default,
							valueTransformer: body.value_transformer,
							actualValue: body.actual_value,
							expectedValue: body.expected_value,
							value: body.value,
							pending: body.pending,
							isValid: body.is_valid,
							deviceId: body.device,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

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

		const insertData = async (payload: IDevicePropertiesInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: DevicePropertyDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const deviceIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<DevicePropertyDocument>(exchangeDocumentSchema);

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
							entity: 'property',
						},
						category: doc.category,
						identifier: doc.identifier,
						name: doc.name,
						settable: doc.settable,
						queryable: doc.queryable,
						dataType: doc.data_type,
						unit: doc.unit,
						format: doc.format,
						invalid: doc.invalid,
						scale: doc.scale,
						step: doc.step,
						valueTransformer: doc.value_transformer,
						default: doc.default,
						value: doc.value,
						deviceId: doc.device,
						parentId: doc.parent,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IDevicePropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DEVICES_PROPERTIES);

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

		const loadRecord = async (payload: IDevicePropertiesLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IDevicePropertyDatabaseRecord>(payload.id, DB_TABLE_DEVICES_PROPERTIES);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IDevicePropertiesLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IDevicePropertyDatabaseRecord>(DB_TABLE_DEVICES_PROPERTIES);

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
			findByIdentifier,
			findForDevice,
			findMeta,
			set,
			unset,
			get,
			fetch,
			add,
			edit,
			save,
			setState,
			remove,
			socketData,
			insertData,
			loadRecord,
			loadAllRecords,
		};
	}
);

export const registerDevicesPropertiesStore = (pinia: Pinia): Store<string, IDevicePropertiesState, object, IDevicePropertiesActions> => {
	return useDeviceProperties(pinia);
};
