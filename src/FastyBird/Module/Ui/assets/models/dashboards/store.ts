import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.dashboard.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	IDashboardDatabaseRecord,
	IDashboardMeta,
	IDashboardsInsertDataActionPayload,
	IDashboardsLoadRecordActionPayload,
	IPlainRelation,
} from '../../models/types';
import { DashboardDocument, RoutingKeys } from '../../types';
import { DB_TABLE_DASHBOARDS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';

import {
	IDashboard,
	IDashboardRecordFactoryPayload,
	IDashboardResponseJson,
	IDashboardResponseModel,
	IDashboardsActions,
	IDashboardsAddActionPayload,
	IDashboardsEditActionPayload,
	IDashboardsFetchActionPayload,
	IDashboardsGetActionPayload,
	IDashboardsGetters,
	IDashboardsRemoveActionPayload,
	IDashboardsResponseJson,
	IDashboardsSaveActionPayload,
	IDashboardsSetActionPayload,
	IDashboardsSocketDataActionPayload,
	IDashboardsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: IDashboardRecordFactoryPayload): IDashboard => {
	const record: IDashboard = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: data.name,
		comment: get(data, 'comment', null),
		priority: get(data, 'priority', 0),

		relationshipNames: ['tabs'],

		tabs: [],

		owner: get(data, 'owner', null),

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},
	};

	record.relationshipNames.forEach((relationName) => {
		get(data, relationName, []).forEach((relation: any): void => {
			if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
				(record[relationName] as IPlainRelation[]).push({
					id: get(relation, 'id', null),
					type: get(relation, 'type', null),
				});
			}
		});
	});

	return record;
};

const databaseRecordFactory = (record: IDashboard): IDashboardDatabaseRecord => {
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

		tabs: record.tabs.map((tab) => ({
			id: tab.id,
			type: { source: tab.type.source, entity: tab.type.entity },
		})),

		owner: record.owner,
	};
};

export const useDashboards = defineStore<string, IDashboardsState, IDashboardsGetters, IDashboardsActions>('ui_module_dashboards', {
	state: (): IDashboardsState => {
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
		firstLoadFinished: (state: IDashboardsState): (() => boolean) => {
			return (): boolean => state.firstLoad;
		},

		getting: (state: IDashboardsState): ((id: IDashboard['id']) => boolean) => {
			return (id: IDashboard['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IDashboardsState): (() => boolean) => {
			return (): boolean => state.semaphore.fetching.items;
		},

		findById: (state: IDashboardsState): ((id: IDashboard['id']) => IDashboard | null) => {
			return (id: IDashboard['id']): IDashboard | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findAll: (state: IDashboardsState): (() => IDashboard[]) => {
			return (): IDashboard[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IDashboardsState): ((id: IDashboard['id']) => IDashboardMeta | null) => {
			return (id: IDashboard['id']): IDashboardMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IDashboardsSetActionPayload} payload
		 */
		async set(payload: IDashboardsSetActionPayload): Promise<IDashboard> {
			const record = storeRecordFactory(payload.data);

			await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DASHBOARDS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IDashboardsGetActionPayload} payload
		 */
		async get(payload: IDashboardsGetActionPayload): Promise<boolean> {
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
				const dashboardResponse = await axios.get<IDashboardResponseJson>(`/${ModulePrefix.UI}/v1/dashboards/${payload.id}`);

				const dashboardResponseModel = jsonApiFormatter.deserialize(dashboardResponse.data) as IDashboardResponseModel;

				this.data = this.data ?? {};
				this.data[dashboardResponseModel.id] = storeRecordFactory(dashboardResponseModel);

				await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(this.data[dashboardResponseModel.id]), DB_TABLE_DASHBOARDS);

				this.meta[dashboardResponseModel.id] = dashboardResponseModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.dashboards.get.failed', e, 'Fetching dashboard failed.');
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
		 * @param {IDashboardsFetchActionPayload} payload
		 */
		async fetch(payload?: IDashboardsFetchActionPayload): Promise<boolean> {
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
				const dashboardsResponse = await axios.get<IDashboardsResponseJson>(`/${ModulePrefix.UI}/v1/dashboards`);

				const dashboardsResponseModel = jsonApiFormatter.deserialize(dashboardsResponse.data) as IDashboardResponseModel[];

				for (const dashboard of dashboardsResponseModel) {
					this.data = this.data ?? {};
					this.data[dashboard.id] = storeRecordFactory(dashboard);

					await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(this.data[dashboard.id]), DB_TABLE_DASHBOARDS);

					this.meta[dashboard.id] = dashboard.type;
				}

				this.firstLoad = true;

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IDashboardDatabaseRecord>(DB_TABLE_DASHBOARDS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(this.data ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_DASHBOARDS);

					delete this.meta[id];
				}
			} catch (e: any) {
				throw new ApiError('ui-module.dashboards.fetch.failed', e, 'Fetching dashboards failed.');
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
		 * @param {IDashboardsAddActionPayload} payload
		 */
		async add(payload: IDashboardsAddActionPayload): Promise<IDashboard> {
			const newDashboard = storeRecordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
			});

			this.semaphore.creating.push(newDashboard.id);

			this.data = this.data ?? {};
			this.data[newDashboard.id] = newDashboard;

			if (newDashboard.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDashboard.id);

				return newDashboard;
			} else {
				try {
					const createdDashboard = await axios.post<IDashboardResponseJson>(
						`/${ModulePrefix.UI}/v1/dashboards`,
						jsonApiFormatter.serialize({
							stuff: newDashboard,
						})
					);

					const createdDashboardModel = jsonApiFormatter.deserialize(createdDashboard.data) as IDashboardResponseModel;

					this.data[createdDashboardModel.id] = storeRecordFactory(createdDashboardModel);

					await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(this.data[createdDashboardModel.id]), DB_TABLE_DASHBOARDS);

					this.meta[createdDashboardModel.id] = createdDashboardModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newDashboard.id];

					throw new ApiError('ui-module.dashboards.create.failed', e, 'Create new dashboard failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDashboard.id);
				}

				return this.data[newDashboard.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IDashboardsEditActionPayload} payload
		 */
		async edit(payload: IDashboardsEditActionPayload): Promise<IDashboard> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.dashboards.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.dashboards.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IDashboard;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedDashboard = await axios.patch<IDashboardResponseJson>(
						`/${ModulePrefix.UI}/v1/dashboards/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedDashboardModel = jsonApiFormatter.deserialize(updatedDashboard.data) as IDashboardResponseModel;

					this.data[updatedDashboardModel.id] = storeRecordFactory(updatedDashboardModel);

					await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(this.data[updatedDashboardModel.id]), DB_TABLE_DASHBOARDS);

					this.meta[updatedDashboardModel.id] = updatedDashboardModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.dashboards.update.failed', e, 'Edit dashboard failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IDashboardsSaveActionPayload} payload
		 */
		async save(payload: IDashboardsSaveActionPayload): Promise<IDashboard> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.dashboards.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.dashboards.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedDashboard = await axios.post<IDashboardResponseJson>(
					`/${ModulePrefix.UI}/v1/dashboards`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedDashboardModel = jsonApiFormatter.deserialize(savedDashboard.data) as IDashboardResponseModel;

				this.data[savedDashboardModel.id] = storeRecordFactory(savedDashboardModel);

				await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(this.data[savedDashboardModel.id]), DB_TABLE_DASHBOARDS);

				this.meta[savedDashboardModel.id] = savedDashboardModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.dashboards.save.failed', e, 'Save draft dashboard failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IDashboardsRemoveActionPayload} payload
		 */
		async remove(payload: IDashboardsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('ui-module.dashboards.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_DASHBOARDS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.UI}/v1/dashboards/${payload.id}`);
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.dashboards.delete.failed', e, 'Delete dashboard failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IDashboardsSocketDataActionPayload} payload
		 */
		async socketData(payload: IDashboardsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.DASHBOARD_DOCUMENT_REPORTED,
					RoutingKeys.DASHBOARD_DOCUMENT_CREATED,
					RoutingKeys.DASHBOARD_DOCUMENT_UPDATED,
					RoutingKeys.DASHBOARD_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: DashboardDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<DashboardDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.DASHBOARD_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_DASHBOARDS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					delete this.data[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.DASHBOARD_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
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

						await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DASHBOARDS);

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
		 * @param {IDashboardsInsertDataActionPayload} payload
		 */
		async insertData(payload: IDashboardsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: DashboardDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<DashboardDocument>(exchangeDocumentSchema);

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
							entity: 'dashboard',
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

				await addRecord<IDashboardDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_DASHBOARDS);

				this.meta[record.id] = record.type;
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IDashboardsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IDashboardsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IDashboardDatabaseRecord>(payload.id, DB_TABLE_DASHBOARDS);

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
			const records = await getAllRecords<IDashboardDatabaseRecord>(DB_TABLE_DASHBOARDS);

			this.data = this.data ?? {};

			for (const record of records) {
				this.data[record.id] = storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerDashboardsStore = (pinia: Pinia): Store => {
	return useDashboards(pinia);
};
