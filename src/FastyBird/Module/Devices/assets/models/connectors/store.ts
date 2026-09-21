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

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.json';
import { connectorControlsStoreKey, connectorPropertiesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ConnectorCategory,
	ConnectorDocument,
	ConnectorPropertyIdentifier,
	ConnectorsStoreSetup,
	IConnectorControlResponseModel,
	IConnectorDatabaseRecord,
	IConnectorMeta,
	IConnectorProperty,
	IConnectorPropertyResponseModel,
	IConnectorsInsertDataActionPayload,
	IConnectorsLoadRecordActionPayload,
	IConnectorsStateSemaphore,
	IConnectorsUnsetActionPayload,
	IPlainRelation,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_CONNECTORS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IConnector,
	IConnectorRecordFactoryPayload,
	IConnectorResponseJson,
	IConnectorResponseModel,
	IConnectorsActions,
	IConnectorsAddActionPayload,
	IConnectorsEditActionPayload,
	IConnectorsFetchActionPayload,
	IConnectorsGetActionPayload,
	IConnectorsRemoveActionPayload,
	IConnectorsResponseJson,
	IConnectorsSaveActionPayload,
	IConnectorsSetActionPayload,
	IConnectorsSocketDataActionPayload,
	IConnectorsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (storesManager: IStoresManager, data: IConnectorRecordFactoryPayload): IConnector => {
	const record: IConnector = {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: lodashGet(data, 'name', null),
		comment: lodashGet(data, 'comment', null),
		enabled: lodashGet(data, 'enabled', false),

		relationshipNames: ['properties', 'controls', 'devices'],

		properties: [],
		controls: [],
		devices: [],

		owner: lodashGet(data, 'owner', null),

		get isEnabled(): boolean {
			return this.enabled;
		},

		get stateProperty(): IConnectorProperty | null {
			const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

			const stateProperty = connectorPropertiesStore
				.findForConnector(this.id)
				.find((property) => property.identifier === ConnectorPropertyIdentifier.STATE);

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
		lodashGet(data, relationName, []).forEach((relation: any): void => {
			if (
				relationName === 'properties' ||
				relationName === 'controls' ||
				(relationName === 'devices' && lodashGet(relation, 'id', null) !== null && lodashGet(relation, 'type', null) !== null)
			) {
				(record[relationName] as IPlainRelation[]).push({
					id: lodashGet(relation, 'id', null),
					type: lodashGet(relation, 'type', null),
				});
			}
		});
	});

	return record;
};

const databaseRecordFactory = (record: IConnector): IConnectorDatabaseRecord => {
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
		enabled: record.enabled,

		relationshipNames: record.relationshipNames.map((name) => name),

		devices: record.devices.map((device) => ({
			id: device.id,
			type: { type: device.type.type, source: device.type.source, entity: device.type.entity },
		})),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
		})),

		owner: record.owner,
	};
};

const addPropertiesRelations = async (
	storesManager: IStoresManager,
	connector: IConnector,
	properties: (IConnectorPropertyResponseModel | IPlainRelation)[]
): Promise<void> => {
	const propertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

	for (const property of properties) {
		if ('identifier' in property) {
			await propertiesStore.set({
				data: {
					...property,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (
	storesManager: IStoresManager,
	connector: IConnector,
	controls: (IConnectorControlResponseModel | IPlainRelation)[]
): Promise<void> => {
	const controlsStore = storesManager.getStore(connectorControlsStoreKey);

	for (const control of controls) {
		if ('identifier' in control) {
			await controlsStore.set({
				data: {
					...control,
					...{
						connectorId: connector.id,
					},
				},
			});
		}
	}
};

export const useConnectors = defineStore<'devices_module_connectors', ConnectorsStoreSetup>('devices_module_connectors', (): ConnectorsStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IConnectorsStateSemaphore>({
		fetching: {
			items: false,
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<boolean>(false);

	const data = ref<{ [key: IConnector['id']]: IConnector } | undefined>(undefined);

	const meta = ref<{ [key: IConnector['id']]: IConnectorMeta }>({});

	const firstLoadFinished = (): boolean => firstLoad.value;

	const getting = (id: IConnector['id']): boolean => semaphore.value.fetching.item.includes(id);

	const fetching = (): boolean => semaphore.value.fetching.items;

	const findById = (id: IConnector['id']): IConnector | null => (id in (data.value ?? {}) ? (data.value ?? {})[id] : null);

	const findAll = (): IConnector[] => Object.values(data.value ?? {});

	const findMeta = (id: IConnector['id']): IConnectorMeta | null => (id in meta.value ? meta.value[id] : null);

	const set = async (payload: IConnectorsSetActionPayload): Promise<IConnector> => {
		const record = storeRecordFactory(storesManager, payload.data);

		if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
			await addPropertiesRelations(storesManager, record, payload.data.properties);
		}

		if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
			await addControlsRelations(storesManager, record, payload.data.controls);
		}

		await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

		meta.value[record.id] = record.type;

		data.value = data.value ?? {};
		return (data.value[record.id] = record);
	};

	const unset = async (payload: IConnectorsUnsetActionPayload): Promise<void> => {
		if (!data.value) {
			return;
		}

		if (payload.id !== undefined) {
			await removeRecord(payload.id, DB_TABLE_CONNECTORS);

			delete meta.value[payload.id];

			delete data.value[payload.id];

			return;
		}

		throw new Error('You have to provide at least connector or device id');
	};

	const get = async (payload: IConnectorsGetActionPayload): Promise<boolean> => {
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
			const connectorResponse = await axios.get<IConnectorResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`);

			const connectorResponseModel = jsonApiFormatter.deserialize(connectorResponse.data) as IConnectorResponseModel;

			data.value = data.value ?? {};
			data.value[connectorResponseModel.id] = storeRecordFactory(storesManager, connectorResponseModel);

			await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(data.value[connectorResponseModel.id]), DB_TABLE_CONNECTORS);

			meta.value[connectorResponseModel.id] = connectorResponseModel.type;
		} catch (e: any) {
			if (e instanceof AxiosError && e.status === 404) {
				await unset({
					id: payload.id,
				});

				return true;
			}

			throw new ApiError('devices-module.connectors.get.failed', e, 'Fetching connector failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const fetch = async (payload?: IConnectorsFetchActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.items) {
			return false;
		}

		const fromDatabase = await loadAllRecords();

		if (fromDatabase && payload?.refresh === false) {
			return true;
		}

		if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
			semaphore.value.fetching.items = true;
		}

		firstLoad.value = false;

		try {
			const connectorsResponse = await axios.get<IConnectorsResponseJson>(`/${ModulePrefix.DEVICES}/v1/connectors`);

			const connectorsResponseModel = jsonApiFormatter.deserialize(connectorsResponse.data) as IConnectorResponseModel[];

			for (const connector of connectorsResponseModel) {
				data.value = data.value ?? {};
				data.value[connector.id] = storeRecordFactory(storesManager, connector);

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(data.value[connector.id]), DB_TABLE_CONNECTORS);

				meta.value[connector.id] = connector.type;
			}

			firstLoad.value = true;

			// Get all current IDs from IndexedDB
			const allRecords = await getAllRecords<IConnectorDatabaseRecord>(DB_TABLE_CONNECTORS);
			const indexedDbIds: string[] = allRecords.map((record) => record.id);

			// Get the IDs from the latest changes
			const serverIds: string[] = Object.keys(data.value ?? {});

			// Find IDs that are in IndexedDB but not in the server response
			const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

			// Remove records that are no longer present on the server
			for (const id of idsToRemove) {
				await removeRecord(id, DB_TABLE_CONNECTORS);

				delete meta.value[id];
			}
		} catch (e: any) {
			throw new ApiError('devices-module.connectors.fetch.failed', e, 'Fetching connectors failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items = false;
			}
		}

		return true;
	};

	const add = async (payload: IConnectorsAddActionPayload): Promise<IConnector> => {
		const newConnector = storeRecordFactory(storesManager, {
			...payload.data,
			...{ id: payload?.id, type: payload?.type, category: ConnectorCategory.GENERIC, draft: payload?.draft },
		});

		semaphore.value.creating.push(newConnector.id);

		data.value = data.value ?? {};
		data.value[newConnector.id] = newConnector;

		if (newConnector.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newConnector.id);

			meta.value[newConnector.id] = newConnector.type;

			return newConnector;
		} else {
			try {
				const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

				const properties = connectorPropertiesStore.findForConnector(newConnector.id);

				const connectorControlsStore = storesManager.getStore(connectorControlsStoreKey);

				const controls = connectorControlsStore.findForConnector(newConnector.id);

				const createdConnector = await axios.post<IConnectorResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors`,
					jsonApiFormatter.serialize({
						stuff: {
							...newConnector,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const createdConnectorModel = jsonApiFormatter.deserialize(createdConnector.data) as IConnectorResponseModel;

				data.value[createdConnectorModel.id] = storeRecordFactory(storesManager, createdConnectorModel);

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(data.value[createdConnectorModel.id]), DB_TABLE_CONNECTORS);

				meta.value[createdConnectorModel.id] = createdConnectorModel.type;
			} catch (e: any) {
				// Record could not be created on api, we have to remove it from database
				delete data.value[newConnector.id];

				throw new ApiError('devices-module.connectors.create.failed', e, 'Create new connector failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newConnector.id);
			}

			return data.value[newConnector.id];
		}
	};

	const edit = async (payload: IConnectorsEditActionPayload): Promise<IConnector> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.connectors.update.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.connectors.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IConnector;

		data.value[payload.id] = storeRecordFactory(storesManager, {
			...updatedRecord,
		});

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

				const properties = connectorPropertiesStore.findForConnector(updatedRecord.id);

				const connectorControlsStore = storesManager.getStore(connectorControlsStoreKey);

				const controls = connectorControlsStore.findForConnector(updatedRecord.id);

				const updatedConnector = await axios.patch<IConnectorResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`,
					jsonApiFormatter.serialize({
						stuff: {
							...updatedRecord,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const updatedConnectorModel = jsonApiFormatter.deserialize(updatedConnector.data) as IConnectorResponseModel;

				data.value[updatedConnectorModel.id] = storeRecordFactory(storesManager, updatedConnectorModel);

				await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(data.value[updatedConnectorModel.id]), DB_TABLE_CONNECTORS);

				meta.value[updatedConnectorModel.id] = updatedConnectorModel.type;
			} catch (e: any) {
				// Updating record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('devices-module.connectors.update.failed', e, 'Edit connector failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}

			return data.value[payload.id];
		}
	};

	const save = async (payload: IConnectorsSaveActionPayload): Promise<IConnector> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.connectors.save.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.connectors.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

			const properties = connectorPropertiesStore.findForConnector(recordToSave.id);

			const connectorControlsStore = storesManager.getStore(connectorControlsStoreKey);

			const controls = connectorControlsStore.findForConnector(recordToSave.id);

			const savedConnector = await axios.post<IConnectorResponseJson>(
				`/${ModulePrefix.DEVICES}/v1/connectors`,
				jsonApiFormatter.serialize({
					stuff: {
						...recordToSave,
						properties,
						controls,
					},
					includeNames: ['properties', 'controls'],
				})
			);

			const savedConnectorModel = jsonApiFormatter.deserialize(savedConnector.data) as IConnectorResponseModel;

			data.value[savedConnectorModel.id] = storeRecordFactory(storesManager, savedConnectorModel);

			await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(data.value[savedConnectorModel.id]), DB_TABLE_CONNECTORS);

			meta.value[savedConnectorModel.id] = savedConnectorModel.type;
		} catch (e: any) {
			throw new ApiError('devices-module.connectors.save.failed', e, 'Save draft connector failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}

		return data.value[payload.id];
	};

	const remove = async (payload: IConnectorsRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('devices-module.connectors.delete.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			return true;
		}

		const propertiesStore = storesManager.getStore(connectorPropertiesStoreKey);
		const controlsStore = storesManager.getStore(connectorControlsStoreKey);

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		await removeRecord(payload.id, DB_TABLE_CONNECTORS);

		delete meta.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

			propertiesStore.unset({ connector: recordToDelete });
			controlsStore.unset({ connector: recordToDelete });
		} else {
			try {
				await axios.delete(`/${ModulePrefix.DEVICES}/v1/connectors/${payload.id}`);

				propertiesStore.unset({ connector: recordToDelete });
				controlsStore.unset({ connector: recordToDelete });
			} catch (e: any) {
				// Deleting record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('devices-module.connectors.delete.failed', e, 'Delete connector failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const socketData = async (payload: IConnectorsSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.CONNECTOR_DOCUMENT_REPORTED,
				RoutingKeys.CONNECTOR_DOCUMENT_CREATED,
				RoutingKeys.CONNECTOR_DOCUMENT_UPDATED,
				RoutingKeys.CONNECTOR_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: ConnectorDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<ConnectorDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (payload.routingKey === RoutingKeys.CONNECTOR_DOCUMENT_DELETED) {
			await removeRecord(body.id, DB_TABLE_CONNECTORS);

			delete meta.value[body.id];

			if (data.value && body.id in data.value) {
				const recordToDelete = data.value[body.id];

				delete data.value[body.id];

				const propertiesStore = storesManager.getStore(connectorPropertiesStoreKey);
				const controlsStore = storesManager.getStore(connectorControlsStoreKey);

				propertiesStore.unset({ connector: recordToDelete });
				controlsStore.unset({ connector: recordToDelete });
			}
		} else {
			if (payload.routingKey === RoutingKeys.CONNECTOR_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			if (data.value && body.id in data.value) {
				const record = storeRecordFactory(storesManager, {
					...data.value[body.id],
					...{
						category: body.category,
						name: body.name,
						comment: body.comment,
						enabled: body.enabled,
						owner: body.owner,
					},
				});

				if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
					data.value[body.id] = record;

					await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

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

	const insertData = async (payload: IConnectorsInsertDataActionPayload): Promise<boolean> => {
		data.value = data.value ?? {};

		let documents: ConnectorDocument[];

		if (Array.isArray(payload.data)) {
			documents = payload.data;
		} else {
			documents = [payload.data];
		}

		for (const doc of documents) {
			const isValid = jsonSchemaValidator.compile<ConnectorDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(doc)) {
					return false;
				}
			} catch {
				return false;
			}

			const record = storeRecordFactory(storesManager, {
				...data.value[doc.id],
				...{
					id: doc.id,
					type: {
						type: doc.type,
						source: doc.source,
						entity: 'connector',
					},
					category: doc.category,
					identifier: doc.identifier,
					name: doc.name,
					comment: doc.comment,
					enabled: doc.enabled,
					owner: doc.owner,
				},
			});

			if (documents.length === 1) {
				data.value[doc.id] = record;
			}

			await addRecord<IConnectorDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS);

			meta.value[record.id] = record.type;
		}

		return true;
	};

	const loadRecord = async (payload: IConnectorsLoadRecordActionPayload): Promise<boolean> => {
		const record = await getRecord<IConnectorDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS);

		if (record) {
			data.value = data.value ?? {};
			data.value[payload.id] = storeRecordFactory(storesManager, record);

			return true;
		}

		return false;
	};

	const loadAllRecords = async (): Promise<boolean> => {
		const records = await getAllRecords<IConnectorDatabaseRecord>(DB_TABLE_CONNECTORS);

		data.value = data.value ?? {};

		for (const record of records) {
			data.value[record.id] = storeRecordFactory(storesManager, record);
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

export const registerConnectorsStore = (pinia: Pinia): Store<string, IConnectorsState, object, IConnectorsActions> => {
	return useConnectors(pinia);
};
