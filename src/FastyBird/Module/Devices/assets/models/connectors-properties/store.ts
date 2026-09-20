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

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.property.json';
import { connectorsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ConnectorPropertiesStoreSetup,
	ConnectorPropertyDocument,
	IConnector,
	IConnectorPropertiesInsertDataActionPayload,
	IConnectorPropertiesLoadAllRecordsActionPayload,
	IConnectorPropertiesLoadRecordActionPayload,
	IConnectorPropertiesSetStateActionPayload,
	IConnectorPropertiesStateSemaphore,
	IConnectorPropertyDatabaseRecord,
	IConnectorPropertyMeta,
	PropertyCategory,
	RoutingKeys,
} from '../../types';
import { PropertyType } from '../../types';
import { DB_TABLE_CONNECTORS_PROPERTIES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IConnectorPropertiesActions,
	IConnectorPropertiesAddActionPayload,
	IConnectorPropertiesEditActionPayload,
	IConnectorPropertiesFetchActionPayload,
	IConnectorPropertiesGetActionPayload,
	IConnectorPropertiesRemoveActionPayload,
	IConnectorPropertiesResponseJson,
	IConnectorPropertiesSaveActionPayload,
	IConnectorPropertiesSetActionPayload,
	IConnectorPropertiesSocketDataActionPayload,
	IConnectorPropertiesState,
	IConnectorPropertiesUnsetActionPayload,
	IConnectorProperty,
	IConnectorPropertyRecordFactoryPayload,
	IConnectorPropertyResponseJson,
	IConnectorPropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IConnectorPropertyRecordFactoryPayload): Promise<IConnectorProperty> => {
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
			throw new Error("Connector for property couldn't be loaded from store");
		}

		if (
			connectorsStore.findById(data.connectorId as string) === null &&
			!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))
		) {
			throw new Error("Connector for property couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for property couldn't be loaded from store");
		}

		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	const record: IConnectorProperty = {
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
		relationshipNames: ['connector'],

		connector: {
			id: connector.id,
			type: connector.type,
		},

		get title(): string {
			return this.name ?? this.identifier.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
		},
	};

	return record;
};

const databaseRecordFactory = (record: IConnectorProperty): IConnectorPropertyDatabaseRecord => {
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

		connector: {
			id: record.connector.id,
			type: {
				type: record.connector.type.type,
				source: record.connector.type.source,
				entity: record.connector.type.entity,
			},
		},
	};
};

export const useConnectorProperties = defineStore<'devices_module_connectors_properties', ConnectorPropertiesStoreSetup>(
	'devices_module_connectors_properties',
	(): ConnectorPropertiesStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IConnectorPropertiesStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IConnector['id'][]>([]);

		const data = ref<{ [key: IConnectorProperty['id']]: IConnectorProperty } | undefined>(undefined);

		const meta = ref<{ [key: IConnectorProperty['id']]: IConnectorPropertyMeta }>({});

		const firstLoadFinished = (connectorId: IConnector['id']): boolean => firstLoad.value.includes(connectorId);

		const getting = (id: IConnectorProperty['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (connectorId: IConnector['id'] | null): boolean =>
			connectorId !== null ? semaphore.value.fetching.items.includes(connectorId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IConnectorProperty['id']): IConnectorProperty | null => {
			const property: IConnectorProperty | undefined = Object.values(data.value ?? {}).find(
				(property: IConnectorProperty): boolean => property.id === id
			);

			return property ?? null;
		};

		const findByIdentifier = (connector: IConnector, identifier: IConnectorProperty['identifier']): IConnectorProperty | null => {
			const property: IConnectorProperty | undefined = Object.values(data.value ?? {}).find((property: IConnectorProperty): boolean => {
				return property.connector.id === connector.id && property.identifier.toLowerCase() === identifier.toLowerCase();
			});

			return property ?? null;
		};

		const findForConnector = (connectorId: IConnector['id']): IConnectorProperty[] =>
			Object.values(data.value ?? {}).filter((property: IConnectorProperty): boolean => property.connector.id === connectorId);

		const findMeta = (id: IConnectorProperty['id']): IConnectorPropertyMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IConnectorPropertiesSetActionPayload): Promise<IConnectorProperty> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IConnectorPropertiesUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.connector !== undefined) {
				const items = findForConnector(payload.connector.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_CONNECTORS_PROPERTIES);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least connector or property id');
		};

		const get = async (payload: IConnectorPropertiesGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const propertyResponse = await axios.get<IConnectorPropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/properties/${payload.id}`
				);

				const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IConnectorPropertyResponseModel;

				data.value = data.value ?? {};
				data.value[propertyResponseModel.id] = await storeRecordFactory(storesManager, {
					...propertyResponseModel,
					...{ connectorId: propertyResponseModel.connector.id },
				});

				await addRecord<IConnectorPropertyDatabaseRecord>(
					databaseRecordFactory(data.value[propertyResponseModel.id]),
					DB_TABLE_CONNECTORS_PROPERTIES
				);

				meta.value[propertyResponseModel.id] = propertyResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.connector-properties.get.failed', e, 'Fetching property failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IConnectorPropertiesFetchActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.items.includes(payload.connector.id)) {
				return false;
			}

			const fromDatabase = await loadAllRecords({ connector: payload.connector });

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items.push(payload.connector.id);
			}

			firstLoad.value = firstLoad.value.filter((item) => item !== payload.connector.id);
			firstLoad.value = [...new Set(firstLoad.value)];

			try {
				const propertiesResponse = await axios.get<IConnectorPropertiesResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/properties`
				);

				const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IConnectorPropertyResponseModel[];

				for (const property of propertiesResponseModel) {
					data.value = data.value ?? {};
					data.value[property.id] = await storeRecordFactory(storesManager, {
						...property,
						...{ connectorId: property.connector.id },
					});

					await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(data.value[property.id]), DB_TABLE_CONNECTORS_PROPERTIES);

					meta.value[property.id] = property.type;
				}

				firstLoad.value.push(payload.connector.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IConnectorPropertyDatabaseRecord>(DB_TABLE_CONNECTORS_PROPERTIES);
				const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connector.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CONNECTORS_PROPERTIES);

					delete meta.value[id];
				}
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					try {
						const connectorsStore = storesManager.getStore(connectorsStoreKey);

						await connectorsStore.get({
							id: payload.connector.id,
						});
					} catch (e: any) {
						if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
							const connectorsStore = storesManager.getStore(connectorsStoreKey);

							connectorsStore.unset({
								id: payload.connector.id,
							});

							return true;
						}
					}
				}

				throw new ApiError('devices-module.connector-properties.fetch.failed', e, 'Fetching properties failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.connector.id);
			}

			return true;
		};

		const add = async (payload: IConnectorPropertiesAddActionPayload): Promise<IConnectorProperty> => {
			const newProperty = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					category: PropertyCategory.GENERIC,
					draft: payload?.draft,
					connectorId: payload.connector.id,
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
				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(payload.connector.id);

				if (connector === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);

					throw new Error('devices-module.connector-properties.create.failed');
				}

				try {
					const apiData: Partial<IConnectorProperty> = {
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
						connector: newProperty.connector,
						relationshipNames: ['connector'],
					};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const createdProperty = await axios.post<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IConnectorPropertyResponseModel;

					data.value[createdPropertyModel.id] = await storeRecordFactory(storesManager, {
						...createdPropertyModel,
						...{ connectorId: createdPropertyModel.connector.id },
					});

					await addRecord<IConnectorPropertyDatabaseRecord>(
						databaseRecordFactory(data.value[createdPropertyModel.id]),
						DB_TABLE_CONNECTORS_PROPERTIES
					);

					meta.value[createdPropertyModel.id] = createdPropertyModel.type;

					return data.value[createdPropertyModel.id];
				} catch (e: any) {
					// Transformer could not be created on api, we have to remove it from database
					delete data.value[newProperty.id];

					throw new ApiError('devices-module.connector-properties.create.failed', e, 'Create new property failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);
				}
			}
		};

		const edit = async (payload: IConnectorPropertiesEditActionPayload): Promise<IConnectorProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.connector-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			const updatedRecord = {
				...existingRecord,
				...payload.data,
			} as IConnectorProperty;

			data.value[payload.id] = await storeRecordFactory(storesManager, {
				...updatedRecord,
			});

			if (updatedRecord.draft) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				return data.value[payload.id];
			} else {
				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(updatedRecord.connector.id);

				if (connector === null) {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.connector-properties.update.failed');
				}

				try {
					const apiData: Partial<IConnectorProperty> = {
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
						connector: updatedRecord.connector,
						relationshipNames: ['connector'],
					};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const updatedProperty = await axios.patch<IConnectorPropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${updatedRecord.connector.id}/properties/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IConnectorPropertyResponseModel;

					data.value[updatedPropertyModel.id] = await storeRecordFactory(storesManager, {
						...updatedPropertyModel,
						...{ connectorId: updatedPropertyModel.connector.id },
					});

					await addRecord<IConnectorPropertyDatabaseRecord>(
						databaseRecordFactory(data.value[updatedPropertyModel.id]),
						DB_TABLE_CONNECTORS_PROPERTIES
					);

					meta.value[updatedPropertyModel.id] = updatedPropertyModel.type;

					return data.value[updatedPropertyModel.id];
				} catch (e: any) {
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					const connector = connectorsStore.findById(updatedRecord.connector.id);

					if (connector !== null) {
						// Updating entity on api failed, we need to refresh entity
						await get({ connector, id: payload.id });
					}

					throw new ApiError('devices-module.connector-properties.update.failed', e, 'Edit property failed.');
				} finally {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
				}
			}
		};

		const save = async (payload: IConnectorPropertiesSaveActionPayload): Promise<IConnectorProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.connector-properties.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-properties.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const connectorsStore = storesManager.getStore(connectorsStoreKey);

			const connector = connectorsStore.findById(recordToSave.connector.id);

			if (connector === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.connector-properties.save.failed');
			}

			try {
				const apiData: Partial<IConnectorProperty> = {
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
					connector: recordToSave.connector,
					relationshipNames: ['connector'],
				};

				if (apiData?.type?.type === PropertyType.DYNAMIC) {
					delete apiData.value;
				}

				if (apiData?.type?.type === PropertyType.VARIABLE) {
					delete apiData.settable;
					delete apiData.queryable;
				}

				const savedProperty = await axios.post<IConnectorPropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${recordToSave.connector.id}/properties`,
					jsonApiFormatter.serialize({
						stuff: apiData,
					})
				);

				const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IConnectorPropertyResponseModel;

				data.value[savedPropertyModel.id] = await storeRecordFactory(storesManager, {
					...savedPropertyModel,
					...{ connectorId: savedPropertyModel.connector.id },
				});

				await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(data.value[savedPropertyModel.id]), DB_TABLE_CONNECTORS_PROPERTIES);

				meta.value[savedPropertyModel.id] = savedPropertyModel.type;

				return data.value[savedPropertyModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.connector-properties.save.failed', e, 'Save draft property failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const setState = async (payload: IConnectorPropertiesSetStateActionPayload): Promise<IConnectorProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.connector-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			data.value[payload.id] = {
				...existingRecord,
				...payload.data,
			} as IConnectorProperty;

			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		};

		const remove = async (payload: IConnectorPropertiesRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.connector-properties.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-properties.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const connectorsStore = storesManager.getStore(connectorsStoreKey);

			const connector = connectorsStore.findById(recordToDelete.connector.id);

			if (connector === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.connector-properties.delete.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/connectors/${recordToDelete.connector.id}/properties/${recordToDelete.id}`);
				} catch (e: any) {
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					const connector = connectorsStore.findById(recordToDelete.connector.id);

					if (connector !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ connector, id: payload.id });
					}

					throw new ApiError('devices-module.connector-properties.delete.failed', e, 'Delete property failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const socketData = async (payload: IConnectorPropertiesSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_CREATED,
					RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_UPDATED,
					RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ConnectorPropertyDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ConnectorPropertyDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CONNECTORS_PROPERTIES);

				delete meta.value[body.id];

				if (data.value && body.id in data.value) {
					delete data.value[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.CONNECTOR_PROPERTY_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
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
							connectorId: body.connector,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

						meta.value[record.id] = record.type;
					}
				} else {
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					const connector = connectorsStore.findById(body.connector);

					if (connector !== null) {
						try {
							await get({
								connector,
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

		const insertData = async (payload: IConnectorPropertiesInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: ConnectorPropertyDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const connectorIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ConnectorPropertyDocument>(exchangeDocumentSchema);

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
							parent: 'connector',
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
						connectorId: doc.connector,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IConnectorPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_PROPERTIES);

				meta.value[record.id] = record.type;

				connectorIds.push(doc.connector);
			}

			if (documents.length > 1) {
				const uniqueConnectorIds = [...new Set(connectorIds)];

				for (const connectorId of uniqueConnectorIds) {
					firstLoad.value.push(connectorId);
					firstLoad.value = [...new Set(firstLoad.value)];
				}
			}

			return true;
		};

		const loadRecord = async (payload: IConnectorPropertiesLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IConnectorPropertyDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS_PROPERTIES);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IConnectorPropertiesLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IConnectorPropertyDatabaseRecord>(DB_TABLE_CONNECTORS_PROPERTIES);

			data.value = data.value ?? {};

			for (const record of records) {
				if (payload?.connector && payload?.connector.id !== record?.connector.id) {
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
			findForConnector,
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

export const registerConnectorsPropertiesStore = (pinia: Pinia): Store<string, IConnectorPropertiesState, object, IConnectorPropertiesActions> => {
	return useConnectorProperties(pinia);
};
