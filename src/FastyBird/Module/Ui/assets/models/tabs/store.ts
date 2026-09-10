import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix, ModuleSource } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.tab.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { IPlainRelation, ITabDatabaseRecord, ITabMeta, ITabsInsertDataActionPayload, ITabsLoadRecordActionPayload } from '../../models/types';
import { RoutingKeys, TabDocument } from '../../types';
import { DB_TABLE_TABS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';

import {
	ITab,
	ITabRecordFactoryPayload,
	ITabResponseJson,
	ITabResponseModel,
	ITabsActions,
	ITabsAddActionPayload,
	ITabsEditActionPayload,
	ITabsFetchActionPayload,
	ITabsGetActionPayload,
	ITabsGetters,
	ITabsRemoveActionPayload,
	ITabsResponseJson,
	ITabsSaveActionPayload,
	ITabsSetActionPayload,
	ITabsSocketDataActionPayload,
	ITabsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: ITabRecordFactoryPayload): ITab => {
	const record: ITab = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: data.name,
		comment: get(data, 'comment', null),
		priority: get(data, 'priority', 0),

		relationshipNames: ['widgets', 'dashboard'],

		dashboard: {
			id: 'N/A',
			type: {
				source: ModuleSource.UI,
				entity: 'dashboard',
			},
		},
		widgets: [],

		owner: get(data, 'owner', null),

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},
	};

	record.relationshipNames.forEach((relationName) => {
		const relation = get(data, relationName, null);

		if (Array.isArray(relation)) {
			relation.forEach((relation: any): void => {
				if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: get(relation, 'id', null),
						type: get(relation, 'type', null),
					});
				}
			});
		} else if (relationName === 'dashboard') {
			(record[relationName] as IPlainRelation) = {
				id: get(relation, 'id', 'N/A'),
				type: get(relation, 'type', {
					source: ModuleSource.UI,
					entity: 'display',
				}),
			};
		}
	});

	return record;
};

const databaseRecordFactory = (record: ITab): ITabDatabaseRecord => {
	return {
		id: record.id,
		type: {
			source: record.type.source,
			entity: record.type.entity,
		},

		identifier: record.identifier,
		name: record.name,
		comment: record.comment,
		priority: record.priority,

		relationshipNames: record.relationshipNames.map((name) => name),

		dashboard: {
			id: record.dashboard.id,
			type: { source: record.dashboard.type.source, entity: record.dashboard.type.entity },
		},
		widgets: record.widgets.map((widget) => ({
			id: widget.id,
			type: { source: widget.type.source, entity: widget.type.entity },
		})),

		owner: record.owner,
	};
};

export const useTabs = defineStore<string, ITabsState, ITabsGetters, ITabsActions>('ui_module_tabs', {
	state: (): ITabsState => {
		return {
			semaphore: {
				fetching: {
					items: false,
					item: [],
				},
				creating: [],
				updating: [],
				deleting: [],
			},

			firstLoad: false,

			data: undefined,
			meta: {},
		};
	},

	getters: {
		firstLoadFinished: (state: ITabsState): (() => boolean) => {
			return (): boolean => state.firstLoad;
		},

		getting: (state: ITabsState): ((id: ITab['id']) => boolean) => {
			return (id: ITab['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: ITabsState): (() => boolean) => {
			return (): boolean => state.semaphore.fetching.items;
		},

		findById: (state: ITabsState): ((id: ITab['id']) => ITab | null) => {
			return (id: ITab['id']): ITab | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findAll: (state: ITabsState): (() => ITab[]) => {
			return (): ITab[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: ITabsState): ((id: ITab['id']) => ITabMeta | null) => {
			return (id: ITab['id']): ITabMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {ITabsSetActionPayload} payload
		 */
		async set(payload: ITabsSetActionPayload): Promise<ITab> {
			const record = storeRecordFactory(payload.data);

			await addRecord<ITabDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_TABS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {ITabsGetActionPayload} payload
		 */
		async get(payload: ITabsGetActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await this.loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.item.push(payload.id);
			}

			try {
				const tabResponse = await axios.get<ITabResponseJson>(`/${ModulePrefix.UI}/v1/tabs/${payload.id}`);

				const tabResponseModel = jsonApiFormatter.deserialize(tabResponse.data) as ITabResponseModel;

				this.data = this.data ?? {};
				this.data[tabResponseModel.id] = storeRecordFactory(tabResponseModel);

				await addRecord<ITabDatabaseRecord>(databaseRecordFactory(this.data[tabResponseModel.id]), DB_TABLE_TABS);

				this.meta[tabResponseModel.id] = tabResponseModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.tabs.get.failed', e, 'Fetching tab failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {ITabsFetchActionPayload} payload
		 */
		async fetch(payload?: ITabsFetchActionPayload): Promise<boolean> {
			if (this.semaphore.fetching.items) {
				return false;
			}

			const fromDatabase = await this.loadAllRecords();

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				this.semaphore.fetching.items = true;
			}

			this.firstLoad = false;

			try {
				const tabsResponse = await axios.get<ITabsResponseJson>(`/${ModulePrefix.UI}/v1/tabs`);

				const tabsResponseModel = jsonApiFormatter.deserialize(tabsResponse.data) as ITabResponseModel[];

				for (const tab of tabsResponseModel) {
					this.data = this.data ?? {};
					this.data[tab.id] = storeRecordFactory(tab);

					await addRecord<ITabDatabaseRecord>(databaseRecordFactory(this.data[tab.id]), DB_TABLE_TABS);

					this.meta[tab.id] = tab.type;
				}

				this.firstLoad = true;

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<ITabDatabaseRecord>(DB_TABLE_TABS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(this.data ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_TABS);

					delete this.meta[id];
				}
			} catch (e: any) {
				throw new ApiError('ui-module.tabs.fetch.failed', e, 'Fetching tabs failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items = false;
				}
			}

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {ITabsAddActionPayload} payload
		 */
		async add(payload: ITabsAddActionPayload): Promise<ITab> {
			const newTab = storeRecordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
			});

			this.semaphore.creating.push(newTab.id);

			this.data = this.data ?? {};
			this.data[newTab.id] = newTab;

			if (newTab.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newTab.id);

				return newTab;
			} else {
				try {
					const createdTab = await axios.post<ITabResponseJson>(
						`/${ModulePrefix.UI}/v1/tabs`,
						jsonApiFormatter.serialize({
							stuff: newTab,
						})
					);

					const createdTabModel = jsonApiFormatter.deserialize(createdTab.data) as ITabResponseModel;

					this.data[createdTabModel.id] = storeRecordFactory(createdTabModel);

					await addRecord<ITabDatabaseRecord>(databaseRecordFactory(this.data[createdTabModel.id]), DB_TABLE_TABS);

					this.meta[createdTabModel.id] = createdTabModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newTab.id];

					throw new ApiError('ui-module.tabs.create.failed', e, 'Create new tab failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newTab.id);
				}

				return this.data[newTab.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {ITabsEditActionPayload} payload
		 */
		async edit(payload: ITabsEditActionPayload): Promise<ITab> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.tabs.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.tabs.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as ITab;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedTab = await axios.patch<ITabResponseJson>(
						`/${ModulePrefix.UI}/v1/tabs/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedTabModel = jsonApiFormatter.deserialize(updatedTab.data) as ITabResponseModel;

					this.data[updatedTabModel.id] = storeRecordFactory(updatedTabModel);

					await addRecord<ITabDatabaseRecord>(databaseRecordFactory(this.data[updatedTabModel.id]), DB_TABLE_TABS);

					this.meta[updatedTabModel.id] = updatedTabModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.tabs.update.failed', e, 'Edit tab failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {ITabsSaveActionPayload} payload
		 */
		async save(payload: ITabsSaveActionPayload): Promise<ITab> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.tabs.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.tabs.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedTab = await axios.post<ITabResponseJson>(
					`/${ModulePrefix.UI}/v1/tabs`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedTabModel = jsonApiFormatter.deserialize(savedTab.data) as ITabResponseModel;

				this.data[savedTabModel.id] = storeRecordFactory(savedTabModel);

				await addRecord<ITabDatabaseRecord>(databaseRecordFactory(this.data[savedTabModel.id]), DB_TABLE_TABS);

				this.meta[savedTabModel.id] = savedTabModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.tabs.save.failed', e, 'Save draft tab failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {ITabsRemoveActionPayload} payload
		 */
		async remove(payload: ITabsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('ui-module.tabs.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_TABS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.UI}/v1/tabs/${payload.id}`);
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.tabs.delete.failed', e, 'Delete tab failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {ITabsSocketDataActionPayload} payload
		 */
		async socketData(payload: ITabsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.TAB_DOCUMENT_REPORTED,
					RoutingKeys.TAB_DOCUMENT_CREATED,
					RoutingKeys.TAB_DOCUMENT_UPDATED,
					RoutingKeys.TAB_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: TabDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<TabDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.TAB_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_TABS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					delete this.data[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.TAB_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (this.data && body.id in this.data) {
					const record = storeRecordFactory({
						...this.data[body.id],
						...{
							name: body.name,
							comment: body.comment,
							priority: body.priority,
							owner: body.owner,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
						this.data[body.id] = record;

						await addRecord<ITabDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_TABS);

						this.meta[record.id] = record.type;
					}
				} else {
					try {
						await this.get({ id: body.id });
					} catch {
						return false;
					}
				}
			}

			return true;
		},

		/**
		 * Insert data from SSR
		 *
		 * @param {ITabsInsertDataActionPayload} payload
		 */
		async insertData(payload: ITabsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: TabDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<TabDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(doc)) {
						return false;
					}
				} catch {
					return false;
				}

				const record = storeRecordFactory({
					...this.data[doc.id],
					...{
						id: doc.id,
						type: {
							source: doc.source,
							entity: 'tab',
						},
						identifier: doc.identifier,
						name: doc.name,
						comment: doc.comment,
						priority: doc.priority,
						owner: doc.owner,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				await addRecord<ITabDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_TABS);

				this.meta[record.id] = record.type;
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {ITabsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: ITabsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<ITabDatabaseRecord>(payload.id, DB_TABLE_TABS);

			if (record) {
				this.data = this.data ?? {};
				this.data[payload.id] = storeRecordFactory(record);

				return true;
			}

			return false;
		},

		/**
		 * Load records from database
		 */
		async loadAllRecords(): Promise<boolean> {
			const records = await getAllRecords<ITabDatabaseRecord>(DB_TABLE_TABS);

			this.data = this.data ?? {};

			for (const record of records) {
				this.data[record.id] = storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerTabsStore = (pinia: Pinia): Store => {
	return useTabs(pinia);
};
