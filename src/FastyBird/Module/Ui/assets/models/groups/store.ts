import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.group.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { IGroupDatabaseRecord, IGroupMeta, IGroupsInsertDataActionPayload, IGroupsLoadRecordActionPayload, IPlainRelation } from '../../models/types';
import { GroupDocument, RoutingKeys } from '../../types';
import { DB_TABLE_GROUPS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';

import {
	IGroup,
	IGroupRecordFactoryPayload,
	IGroupResponseJson,
	IGroupResponseModel,
	IGroupsActions,
	IGroupsAddActionPayload,
	IGroupsEditActionPayload,
	IGroupsFetchActionPayload,
	IGroupsGetActionPayload,
	IGroupsGetters,
	IGroupsRemoveActionPayload,
	IGroupsResponseJson,
	IGroupsSaveActionPayload,
	IGroupsSetActionPayload,
	IGroupsSocketDataActionPayload,
	IGroupsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: IGroupRecordFactoryPayload): IGroup => {
	const record: IGroup = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: data.name,
		comment: get(data, 'comment', null),
		priority: get(data, 'priority', 0),

		relationshipNames: ['widgets'],

		widgets: [],

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

const databaseRecordFactory = (record: IGroup): IGroupDatabaseRecord => {
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

		widgets: record.widgets.map((widget) => ({
			id: widget.id,
			type: { source: widget.type.source, entity: widget.type.entity },
		})),

		owner: record.owner,
	};
};

export const useGroups = defineStore<string, IGroupsState, IGroupsGetters, IGroupsActions>('ui_module_groups', {
	state: (): IGroupsState => {
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
		firstLoadFinished: (state: IGroupsState): (() => boolean) => {
			return (): boolean => state.firstLoad;
		},

		getting: (state: IGroupsState): ((id: IGroup['id']) => boolean) => {
			return (id: IGroup['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IGroupsState): (() => boolean) => {
			return (): boolean => state.semaphore.fetching.items;
		},

		findById: (state: IGroupsState): ((id: IGroup['id']) => IGroup | null) => {
			return (id: IGroup['id']): IGroup | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findAll: (state: IGroupsState): (() => IGroup[]) => {
			return (): IGroup[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IGroupsState): ((id: IGroup['id']) => IGroupMeta | null) => {
			return (id: IGroup['id']): IGroupMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IGroupsSetActionPayload} payload
		 */
		async set(payload: IGroupsSetActionPayload): Promise<IGroup> {
			const record = storeRecordFactory(payload.data);

			await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_GROUPS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IGroupsGetActionPayload} payload
		 */
		async get(payload: IGroupsGetActionPayload): Promise<boolean> {
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
				const groupResponse = await axios.get<IGroupResponseJson>(`/${ModulePrefix.UI}/v1/groups/${payload.id}`);

				const groupResponseModel = jsonApiFormatter.deserialize(groupResponse.data) as IGroupResponseModel;

				this.data = this.data ?? {};
				this.data[groupResponseModel.id] = storeRecordFactory(groupResponseModel);

				await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(this.data[groupResponseModel.id]), DB_TABLE_GROUPS);

				this.meta[groupResponseModel.id] = groupResponseModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.groups.get.failed', e, 'Fetching group failed.');
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
		 * @param {IGroupsFetchActionPayload} payload
		 */
		async fetch(payload?: IGroupsFetchActionPayload): Promise<boolean> {
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
				const groupsResponse = await axios.get<IGroupsResponseJson>(`/${ModulePrefix.UI}/v1/groups`);

				const groupsResponseModel = jsonApiFormatter.deserialize(groupsResponse.data) as IGroupResponseModel[];

				for (const group of groupsResponseModel) {
					this.data = this.data ?? {};
					this.data[group.id] = storeRecordFactory(group);

					await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(this.data[group.id]), DB_TABLE_GROUPS);

					this.meta[group.id] = group.type;
				}

				this.firstLoad = true;

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IGroupDatabaseRecord>(DB_TABLE_GROUPS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(this.data ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_GROUPS);

					delete this.meta[id];
				}
			} catch (e: any) {
				throw new ApiError('ui-module.groups.fetch.failed', e, 'Fetching groups failed.');
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
		 * @param {IGroupsAddActionPayload} payload
		 */
		async add(payload: IGroupsAddActionPayload): Promise<IGroup> {
			const newGroup = storeRecordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
			});

			this.semaphore.creating.push(newGroup.id);

			this.data = this.data ?? {};
			this.data[newGroup.id] = newGroup;

			if (newGroup.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newGroup.id);

				return newGroup;
			} else {
				try {
					const createdGroup = await axios.post<IGroupResponseJson>(
						`/${ModulePrefix.UI}/v1/groups`,
						jsonApiFormatter.serialize({
							stuff: newGroup,
						})
					);

					const createdGroupModel = jsonApiFormatter.deserialize(createdGroup.data) as IGroupResponseModel;

					this.data[createdGroupModel.id] = storeRecordFactory(createdGroupModel);

					await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(this.data[createdGroupModel.id]), DB_TABLE_GROUPS);

					this.meta[createdGroupModel.id] = createdGroupModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newGroup.id];

					throw new ApiError('ui-module.groups.create.failed', e, 'Create new group failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newGroup.id);
				}

				return this.data[newGroup.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IGroupsEditActionPayload} payload
		 */
		async edit(payload: IGroupsEditActionPayload): Promise<IGroup> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.groups.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.groups.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IGroup;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedGroup = await axios.patch<IGroupResponseJson>(
						`/${ModulePrefix.UI}/v1/groups/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedGroupModel = jsonApiFormatter.deserialize(updatedGroup.data) as IGroupResponseModel;

					this.data[updatedGroupModel.id] = storeRecordFactory(updatedGroupModel);

					await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(this.data[updatedGroupModel.id]), DB_TABLE_GROUPS);

					this.meta[updatedGroupModel.id] = updatedGroupModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.groups.update.failed', e, 'Edit group failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IGroupsSaveActionPayload} payload
		 */
		async save(payload: IGroupsSaveActionPayload): Promise<IGroup> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.groups.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.groups.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedGroup = await axios.post<IGroupResponseJson>(
					`/${ModulePrefix.UI}/v1/groups`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedGroupModel = jsonApiFormatter.deserialize(savedGroup.data) as IGroupResponseModel;

				this.data[savedGroupModel.id] = storeRecordFactory(savedGroupModel);

				await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(this.data[savedGroupModel.id]), DB_TABLE_GROUPS);

				this.meta[savedGroupModel.id] = savedGroupModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.groups.save.failed', e, 'Save draft group failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IGroupsRemoveActionPayload} payload
		 */
		async remove(payload: IGroupsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('ui-module.groups.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_GROUPS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.UI}/v1/groups/${payload.id}`);
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.groups.delete.failed', e, 'Delete group failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IGroupsSocketDataActionPayload} payload
		 */
		async socketData(payload: IGroupsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.GROUP_DOCUMENT_REPORTED,
					RoutingKeys.GROUP_DOCUMENT_CREATED,
					RoutingKeys.GROUP_DOCUMENT_UPDATED,
					RoutingKeys.GROUP_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: GroupDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<GroupDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.GROUP_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_GROUPS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					delete this.data[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.GROUP_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
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

						await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_GROUPS);

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
		 * @param {IGroupsInsertDataActionPayload} payload
		 */
		async insertData(payload: IGroupsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: GroupDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<GroupDocument>(exchangeDocumentSchema);

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
							entity: 'group',
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

				await addRecord<IGroupDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_GROUPS);

				this.meta[record.id] = record.type;
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IGroupsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IGroupsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IGroupDatabaseRecord>(payload.id, DB_TABLE_GROUPS);

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
			const records = await getAllRecords<IGroupDatabaseRecord>(DB_TABLE_GROUPS);

			this.data = this.data ?? {};

			for (const record of records) {
				this.data[record.id] = storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerGroupsStore = (pinia: Pinia): Store => {
	return useGroups(pinia);
};
