import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosError } from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager, useWampV1Client } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.connector.control.json';
import { connectorsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ActionRoutes,
	ConnectorControlDocument,
	ConnectorControlsStoreSetup,
	ExchangeCommand,
	IConnector,
	IConnectorControlsStateSemaphore,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_CONNECTORS_CONTROLS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IConnectorControl,
	IConnectorControlDatabaseRecord,
	IConnectorControlMeta,
	IConnectorControlRecordFactoryPayload,
	IConnectorControlResponseJson,
	IConnectorControlResponseModel,
	IConnectorControlsActions,
	IConnectorControlsAddActionPayload,
	IConnectorControlsFetchActionPayload,
	IConnectorControlsGetActionPayload,
	IConnectorControlsInsertDataActionPayload,
	IConnectorControlsLoadAllRecordsActionPayload,
	IConnectorControlsLoadRecordActionPayload,
	IConnectorControlsRemoveActionPayload,
	IConnectorControlsResponseJson,
	IConnectorControlsSaveActionPayload,
	IConnectorControlsSetActionPayload,
	IConnectorControlsSocketDataActionPayload,
	IConnectorControlsState,
	IConnectorControlsTransmitCommandActionPayload,
	IConnectorControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IConnectorControlRecordFactoryPayload): Promise<IConnectorControl> => {
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
			throw new Error("Connector for control couldn't be loaded from store");
		}

		if (!(await connectorsStore.get({ id: data.connectorId as string, refresh: false }))) {
			throw new Error("Connector for control couldn't be loaded from server");
		}

		connectorMeta = connectorsStore.findMeta(data.connectorId as string);

		if (connectorMeta === null) {
			throw new Error("Connector for control couldn't be loaded from store");
		}

		connector = {
			id: data.connectorId as string,
			type: connectorMeta,
		};
	}

	return {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		name: data.name,

		// Relations
		relationshipNames: ['connector'],

		connector: {
			id: connector.id,
			type: connector.type,
		},
	} as IConnectorControl;
};

const databaseRecordFactory = (record: IConnectorControl): IConnectorControlDatabaseRecord => {
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

export const useConnectorControls = defineStore<'devices_module_connectors_controls', ConnectorControlsStoreSetup>(
	'devices_module_connectors_controls',
	(): ConnectorControlsStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IConnectorControlsStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IConnector['id'][]>([]);

		const data = ref<{ [key: IConnectorControl['id']]: IConnectorControl } | undefined>(undefined);

		const meta = ref<{ [key: IConnectorControl['id']]: IConnectorControlMeta }>({});

		const firstLoadFinished = (connectorId: IConnector['id']): boolean => firstLoad.value.includes(connectorId);

		const getting = (id: IConnectorControl['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (connectorId: IConnector['id'] | null): boolean =>
			connectorId !== null ? semaphore.value.fetching.items.includes(connectorId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IConnectorControl['id']): IConnectorControl | null => {
			const control: IConnectorControl | undefined = Object.values(data.value ?? {}).find((control: IConnectorControl): boolean => control.id === id);

			return control ?? null;
		};

		const findByName = (connector: IConnector, name: IConnectorControl['name']): IConnectorControl | null => {
			const control: IConnectorControl | undefined = Object.values(data.value ?? {}).find((control: IConnectorControl): boolean => {
				return control.connector.id === connector.id && control.name.toLowerCase() === name.toLowerCase();
			});

			return control ?? null;
		};

		const findForConnector = (connectorId: IConnector['id']): IConnectorControl[] =>
			Object.values(data.value ?? {}).filter((control: IConnectorControl): boolean => control.connector.id === connectorId);

		const findMeta = (id: IConnectorControl['id']): IConnectorControlMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IConnectorControlsSetActionPayload): Promise<IConnectorControl> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IConnectorControlsUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.connector !== undefined) {
				const items = findForConnector(payload.connector.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_CONNECTORS_CONTROLS);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least connector or control id');
		};

		const get = async (payload: IConnectorControlsGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const controlResponse = await axios.get<IConnectorControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls/${payload.id}`
				);

				const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IConnectorControlResponseModel;

				data.value = data.value ?? {};
				data.value[controlResponseModel.id] = await storeRecordFactory(storesManager, {
					...controlResponseModel,
					...{ connectorId: controlResponseModel.connector.id },
				});

				await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(data.value[controlResponseModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

				meta.value[controlResponseModel.id] = controlResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.connector-controls.get.failed', e, 'Fetching control failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IConnectorControlsFetchActionPayload): Promise<boolean> => {
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
				const controlsResponse = await axios.get<IConnectorControlsResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls`
				);

				const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IConnectorControlResponseModel[];

				for (const control of controlsResponseModel) {
					data.value = data.value ?? {};
					data.value[control.id] = await storeRecordFactory(storesManager, {
						...control,
						...{ connectorId: control.connector.id },
					});

					await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(data.value[control.id]), DB_TABLE_CONNECTORS_CONTROLS);

					meta.value[control.id] = control.type;
				}

				firstLoad.value.push(payload.connector.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IConnectorControlDatabaseRecord>(DB_TABLE_CONNECTORS_CONTROLS);
				const indexedDbIds: string[] = allRecords.filter((record) => record.connector.id === payload.connector.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CONNECTORS_CONTROLS);

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

				throw new ApiError('devices-module.connector-controls.fetch.failed', e, 'Fetching controls failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.connector.id);
			}

			return true;
		};

		const add = async (payload: IConnectorControlsAddActionPayload): Promise<IConnectorControl> => {
			const newControl = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					connectorId: payload.connector.id,
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
				const connectorsStore = storesManager.getStore(connectorsStoreKey);

				const connector = connectorsStore.findById(payload.connector.id);

				if (connector === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);

					throw new Error('devices-module.connector-controls.get.failed');
				}

				try {
					const createdControl = await axios.post<IConnectorControlResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/connectors/${payload.connector.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: newControl,
						})
					);

					const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IConnectorControlResponseModel;

					data.value[createdControlModel.id] = await storeRecordFactory(storesManager, {
						...createdControlModel,
						...{ connectorId: createdControlModel.connector.id },
					});

					await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(data.value[createdControlModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

					meta.value[createdControlModel.id] = createdControlModel.type;

					return data.value[createdControlModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete data.value[newControl.id];

					throw new ApiError('devices-module.connector-controls.create.failed', e, 'Create new control failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);
				}
			}
		};

		const save = async (payload: IConnectorControlsSaveActionPayload): Promise<IConnectorControl> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.connector-controls.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-controls.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const connectorsStore = storesManager.getStore(connectorsStoreKey);

			const connector = connectorsStore.findById(recordToSave.connector.id);

			if (connector === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.connector-controls.get.failed');
			}

			try {
				const savedControl = await axios.post<IConnectorControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/connectors/${recordToSave.connector.id}/controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IConnectorControlResponseModel;

				data.value[savedControlModel.id] = await storeRecordFactory(storesManager, {
					...savedControlModel,
					...{ connectorId: savedControlModel.connector.id },
				});

				await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(data.value[savedControlModel.id]), DB_TABLE_CONNECTORS_CONTROLS);

				meta.value[savedControlModel.id] = savedControlModel.type;

				return data.value[savedControlModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.connector-controls.save.failed', e, 'Save draft control failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const remove = async (payload: IConnectorControlsRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.connector-controls.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-controls.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const connectorsStore = storesManager.getStore(connectorsStoreKey);

			const connector = connectorsStore.findById(recordToDelete.connector.id);

			if (connector === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.connector-controls.get.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/connectors/${recordToDelete.connector.id}/controls/${recordToDelete.id}`);
				} catch (e: any) {
					const connectorsStore = storesManager.getStore(connectorsStoreKey);

					const connector = connectorsStore.findById(recordToDelete.connector.id);

					if (connector !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ connector, id: payload.id });
					}

					throw new ApiError('devices-module.connector-controls.delete.failed', e, 'Delete control failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const transmitCommand = async (payload: IConnectorControlsTransmitCommandActionPayload): Promise<boolean> => {
			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.connector-controls.transmit.failed');
			}

			const control = data.value[payload.id];

			const connectorsStore = storesManager.getStore(connectorsStoreKey);

			const connector = connectorsStore.findById(control.connector.id);

			if (connector === null) {
				throw new Error('devices-module.connector-controls.transmit.failed');
			}

			const { call } = useWampV1Client<{ data: string }>();

			try {
				const response = await call('', {
					routing_key: ActionRoutes.CONNECTOR_CONTROL,
					source: control.type.source,
					data: {
						action: ExchangeCommand.SET,
						connector: connector.id,
						control: control.id,
						expected_value: payload.value,
					},
				});

				if (lodashGet(response.data, 'response') === 'accepted') {
					return true;
				}
			} catch {
				throw new Error('devices-module.connector-controls.transmit.failed');
			}

			throw new Error('devices-module.connector-controls.transmit.failed');
		};

		const socketData = async (payload: IConnectorControlsSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_REPORTED,
					RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_CREATED,
					RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_UPDATED,
					RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ConnectorControlDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ConnectorControlDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CONNECTOR_CONTROL_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CONNECTORS_CONTROLS);

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
							connectorId: body.connector,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

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

		const insertData = async (payload: IConnectorControlsInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: ConnectorControlDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const connectorIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ConnectorControlDocument>(exchangeDocumentSchema);

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
							entity: 'control',
						},
						name: doc.name,
						connectorId: doc.connector,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IConnectorControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CONNECTORS_CONTROLS);

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

		const loadRecord = async (payload: IConnectorControlsLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IConnectorControlDatabaseRecord>(payload.id, DB_TABLE_CONNECTORS_CONTROLS);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IConnectorControlsLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IConnectorControlDatabaseRecord>(DB_TABLE_CONNECTORS_CONTROLS);

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
			findByName,
			findForConnector,
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

export const registerConnectorsControlsStore = (pinia: Pinia): Store<string, IConnectorControlsState, object, IConnectorControlsActions> => {
	return useConnectorControls(pinia);
};
