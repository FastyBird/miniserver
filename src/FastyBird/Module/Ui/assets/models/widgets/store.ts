import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix, ModuleSource } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.widget.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useWidgetDataSources, useWidgetDisplay } from '../../models';
import {
	IPlainRelation,
	IWidgetDataSourceResponseModel,
	IWidgetDatabaseRecord,
	IWidgetDisplayResponseModel,
	IWidgetMeta,
	IWidgetsInsertDataActionPayload,
	IWidgetsLoadRecordActionPayload,
} from '../../models/types';
import { RoutingKeys, WidgetDocument } from '../../types';
import { DB_TABLE_WIDGETS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';

import {
	IWidget,
	IWidgetRecordFactoryPayload,
	IWidgetResponseJson,
	IWidgetResponseModel,
	IWidgetsActions,
	IWidgetsAddActionPayload,
	IWidgetsEditActionPayload,
	IWidgetsFetchActionPayload,
	IWidgetsGetActionPayload,
	IWidgetsGetters,
	IWidgetsRemoveActionPayload,
	IWidgetsResponseJson,
	IWidgetsSaveActionPayload,
	IWidgetsSetActionPayload,
	IWidgetsSocketDataActionPayload,
	IWidgetsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (data: IWidgetRecordFactoryPayload): IWidget => {
	const record: IWidget = {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		identifier: data.identifier,
		name: get(data, 'name', null),
		comment: get(data, 'comment', null),

		relationshipNames: ['display', 'dataSources', 'tabs', 'groups'],

		display: {
			id: 'N/A',
			type: {
				source: ModuleSource.UI,
				type: 'N/A',
				entity: 'display',
			},
		},
		dataSources: [],
		tabs: [],
		groups: [],

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
		} else if (relationName === 'display') {
			(record[relationName] as IPlainRelation) = {
				id: get(relation, 'id', 'N/A'),
				type: get(relation, 'type', {
					source: ModuleSource.UI,
					type: 'N/A',
					entity: 'display',
				}),
			};
		}
	});

	return record;
};

const databaseRecordFactory = (record: IWidget): IWidgetDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
		},

		identifier: record.identifier,
		name: record.name,
		comment: record.comment,

		relationshipNames: record.relationshipNames.map((name) => name),

		display: {
			id: record.display.id,
			type: { type: record.display.type.type, source: record.display.type.source, entity: record.display.type.entity },
		},
		dataSources: record.dataSources.map((dataSource) => ({
			id: dataSource.id,
			type: { type: dataSource.type.type, source: dataSource.type.source, entity: dataSource.type.entity },
		})),

		tabs: record.tabs.map((tab) => ({
			id: tab.id,
			type: { source: tab.type.source, entity: tab.type.entity },
		})),
		groups: record.groups.map((group) => ({
			id: group.id,
			type: { source: group.type.source, entity: group.type.entity },
		})),

		owner: record.owner,
	};
};

const addDisplayRelation = async (widget: IWidget, display: IWidgetDisplayResponseModel | IPlainRelation): Promise<void> => {
	const displayStore = useWidgetDisplay();

	if ('params' in display) {
		await displayStore.set({
			data: {
				...display,
				...{
					widgetId: widget.id,
				},
			},
		});
	}
};

const addDataSourcesRelations = async (widget: IWidget, dataSources: (IWidgetDataSourceResponseModel | IPlainRelation)[]): Promise<void> => {
	const dataSourcesStore = useWidgetDataSources();

	for (const dataSource of dataSources) {
		if ('params' in dataSource) {
			await dataSourcesStore.set({
				data: {
					...dataSource,
					...{
						widgetId: widget.id,
					},
				},
			});
		}
	}
};

export const useWidgets = defineStore<string, IWidgetsState, IWidgetsGetters, IWidgetsActions>('ui_module_widgets', {
	state: (): IWidgetsState => {
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
		firstLoadFinished: (state: IWidgetsState): (() => boolean) => {
			return (): boolean => state.firstLoad;
		},

		getting: (state: IWidgetsState): ((id: IWidget['id']) => boolean) => {
			return (id: IWidget['id']): boolean => state.semaphore.fetching.item.includes(id);
		},

		fetching: (state: IWidgetsState): (() => boolean) => {
			return (): boolean => state.semaphore.fetching.items;
		},

		findById: (state: IWidgetsState): ((id: IWidget['id']) => IWidget | null) => {
			return (id: IWidget['id']): IWidget | null => {
				return id in (state.data ?? {}) ? (state.data ?? {})[id] : null;
			};
		},

		findAll: (state: IWidgetsState): (() => IWidget[]) => {
			return (): IWidget[] => {
				return Object.values(state.data ?? {});
			};
		},

		findMeta: (state: IWidgetsState): ((id: IWidget['id']) => IWidgetMeta | null) => {
			return (id: IWidget['id']): IWidgetMeta | null => {
				return id in state.meta ? state.meta[id] : null;
			};
		},
	},

	actions: {
		/**
		 * Set record from via other store
		 *
		 * @param {IWidgetsSetActionPayload} payload
		 */
		async set(payload: IWidgetsSetActionPayload): Promise<IWidget> {
			const record = storeRecordFactory(payload.data);

			if ('display' in payload.data && Array.isArray(payload.data.display)) {
				await addDisplayRelation(record, payload.data.display);
			}

			if ('dataSources' in payload.data && Array.isArray(payload.data.dataSources)) {
				await addDataSourcesRelations(record, payload.data.dataSources);
			}

			await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS);

			this.meta[record.id] = record.type;

			this.data = this.data ?? {};
			return (this.data[record.id] = record);
		},

		/**
		 * Get one record from server
		 *
		 * @param {IWidgetsGetActionPayload} payload
		 */
		async get(payload: IWidgetsGetActionPayload): Promise<boolean> {
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
				const widgetResponse = await axios.get<IWidgetResponseJson>(`/${ModulePrefix.UI}/v1/widgets/${payload.id}`);

				const widgetResponseModel = jsonApiFormatter.deserialize(widgetResponse.data) as IWidgetResponseModel;

				this.data = this.data ?? {};
				this.data[widgetResponseModel.id] = storeRecordFactory(widgetResponseModel);

				await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(this.data[widgetResponseModel.id]), DB_TABLE_WIDGETS);

				this.meta[widgetResponseModel.id] = widgetResponseModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.widgets.get.failed', e, 'Fetching widget failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}
			}

			const promises: Promise<boolean>[] = [];

			const displayStore = useWidgetDisplay();
			promises.push(displayStore.fetch({ widget: this.data[payload.id] }));

			const dataSourcesStore = useWidgetDataSources();
			promises.push(dataSourcesStore.fetch({ widget: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('ui-module.widgets.get.failed', e, 'Fetching widget failed.');
			});

			return true;
		},

		/**
		 * Fetch all records from server
		 *
		 * @param {IWidgetsFetchActionPayload} payload
		 */
		async fetch(payload?: IWidgetsFetchActionPayload): Promise<boolean> {
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
				const widgetsResponse = await axios.get<IWidgetsResponseJson>(`/${ModulePrefix.UI}/v1/widgets`);

				const widgetsResponseModel = jsonApiFormatter.deserialize(widgetsResponse.data) as IWidgetResponseModel[];

				for (const widget of widgetsResponseModel) {
					this.data = this.data ?? {};
					this.data[widget.id] = storeRecordFactory(widget);

					await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(this.data[widget.id]), DB_TABLE_WIDGETS);

					this.meta[widget.id] = widget.type;
				}

				this.firstLoad = true;

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IWidgetDatabaseRecord>(DB_TABLE_WIDGETS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(this.data ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_WIDGETS);

					delete this.meta[id];
				}
			} catch (e: any) {
				throw new ApiError('ui-module.widgets.fetch.failed', e, 'Fetching widgets failed.');
			} finally {
				if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
					this.semaphore.fetching.items = false;
				}
			}

			const promises: Promise<boolean>[] = [];

			const displayStore = useWidgetDisplay();
			const dataSourcesStore = useWidgetDataSources();

			for (const widget of Object.values(this.data ?? {})) {
				promises.push(displayStore.fetch({ widget }));
				promises.push(dataSourcesStore.fetch({ widget }));
			}

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('ui-module.widgets.fetch.failed', e, 'Fetching widgets failed.');
			});

			return true;
		},

		/**
		 * Add new record
		 *
		 * @param {IWidgetsAddActionPayload} payload
		 */
		async add(payload: IWidgetsAddActionPayload): Promise<IWidget> {
			const newWidget = storeRecordFactory({
				...payload.data,
				...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
			});

			this.semaphore.creating.push(newWidget.id);

			this.data = this.data ?? {};
			this.data[newWidget.id] = newWidget;

			if (newWidget.draft) {
				this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newWidget.id);

				return newWidget;
			} else {
				try {
					const createdWidget = await axios.post<IWidgetResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets`,
						jsonApiFormatter.serialize({
							stuff: newWidget,
						})
					);

					const createdWidgetModel = jsonApiFormatter.deserialize(createdWidget.data) as IWidgetResponseModel;

					this.data[createdWidgetModel.id] = storeRecordFactory(createdWidgetModel);

					await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(this.data[createdWidgetModel.id]), DB_TABLE_WIDGETS);

					this.meta[createdWidgetModel.id] = createdWidgetModel.type;
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete this.data[newWidget.id];

					throw new ApiError('ui-module.widgets.create.failed', e, 'Create new widget failed.');
				} finally {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newWidget.id);
				}

				const promises: Promise<boolean>[] = [];

				const displayStore = useWidgetDisplay();
				promises.push(displayStore.fetch({ widget: this.data[newWidget.id] }));

				const dataSourcesStore = useWidgetDataSources();
				promises.push(dataSourcesStore.fetch({ widget: this.data[newWidget.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('ui-module.widgets.create.failed', e, 'Create new widget failed.');
				});

				return this.data[newWidget.id];
			}
		},

		/**
		 * Edit existing record
		 *
		 * @param {IWidgetsEditActionPayload} payload
		 */
		async edit(payload: IWidgetsEditActionPayload): Promise<IWidget> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.widgets.update.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.widgets.update.failed');
			}

			this.semaphore.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = this.data[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord, ...payload.data } as IWidget;

			this.data[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

				return this.data[payload.id];
			} else {
				try {
					const updatedWidget = await axios.patch<IWidgetResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${payload.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedWidgetModel = jsonApiFormatter.deserialize(updatedWidget.data) as IWidgetResponseModel;

					this.data[updatedWidgetModel.id] = storeRecordFactory(updatedWidgetModel);

					await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(this.data[updatedWidgetModel.id]), DB_TABLE_WIDGETS);

					this.meta[updatedWidgetModel.id] = updatedWidgetModel.type;
				} catch (e: any) {
					// Updating record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.widgets.update.failed', e, 'Edit widget failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}

				const promises: Promise<boolean>[] = [];

				const displayStore = useWidgetDisplay();
				promises.push(displayStore.fetch({ widget: this.data[payload.id] }));

				const dataSourcesStore = useWidgetDataSources();
				promises.push(dataSourcesStore.fetch({ widget: this.data[payload.id] }));

				Promise.all(promises).catch((e: any): void => {
					throw new ApiError('ui-module.widgets.update.failed', e, 'Edit widget failed.');
				});

				return this.data[payload.id];
			}
		},

		/**
		 * Save draft record on server
		 *
		 * @param {IWidgetsSaveActionPayload} payload
		 */
		async save(payload: IWidgetsSaveActionPayload): Promise<IWidget> {
			if (this.semaphore.updating.includes(payload.id)) {
				throw new Error('ui-module.widgets.save.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				throw new Error('ui-module.widgets.save.failed');
			}

			this.semaphore.updating.push(payload.id);

			const recordToSave = this.data[payload.id];

			try {
				const savedWidget = await axios.post<IWidgetResponseJson>(
					`/${ModulePrefix.UI}/v1/widgets`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedWidgetModel = jsonApiFormatter.deserialize(savedWidget.data) as IWidgetResponseModel;

				this.data[savedWidgetModel.id] = storeRecordFactory(savedWidgetModel);

				await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(this.data[savedWidgetModel.id]), DB_TABLE_WIDGETS);

				this.meta[savedWidgetModel.id] = savedWidgetModel.type;
			} catch (e: any) {
				throw new ApiError('ui-module.widgets.save.failed', e, 'Save draft widget failed.');
			} finally {
				this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const displayStore = useWidgetDisplay();
			promises.push(displayStore.fetch({ widget: this.data[payload.id] }));

			const dataSourcesStore = useWidgetDataSources();
			promises.push(dataSourcesStore.fetch({ widget: this.data[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('ui-module.widgets.save.failed', e, 'Save draft channel failed.');
			});

			return this.data[payload.id];
		},

		/**
		 * Remove existing record from store and server
		 *
		 * @param {IWidgetsRemoveActionPayload} payload
		 */
		async remove(payload: IWidgetsRemoveActionPayload): Promise<boolean> {
			if (this.semaphore.deleting.includes(payload.id)) {
				throw new Error('ui-module.widgets.delete.inProgress');
			}

			if (!this.data || !Object.keys(this.data).includes(payload.id)) {
				return true;
			}

			const displayStore = useWidgetDisplay();
			const dataSourcesStore = useWidgetDataSources();

			this.semaphore.deleting.push(payload.id);

			const recordToDelete = this.data[payload.id];

			delete this.data[payload.id];

			await removeRecord(payload.id, DB_TABLE_WIDGETS);

			delete this.meta[payload.id];

			if (recordToDelete.draft) {
				this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

				displayStore.unset({ widget: recordToDelete });
				dataSourcesStore.unset({ widget: recordToDelete });
			} else {
				try {
					await axios.delete(`/${ModulePrefix.UI}/v1/widgets/${payload.id}`);

					displayStore.unset({ widget: recordToDelete });
					dataSourcesStore.unset({ widget: recordToDelete });
				} catch (e: any) {
					// Deleting record on api failed, we need to refresh record
					await this.get({ id: payload.id });

					throw new ApiError('ui-module.widgets.delete.failed', e, 'Delete widget failed.');
				} finally {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		},

		/**
		 * Receive data from sockets
		 *
		 * @param {IWidgetsSocketDataActionPayload} payload
		 */
		async socketData(payload: IWidgetsSocketDataActionPayload): Promise<boolean> {
			if (
				![
					RoutingKeys.WIDGET_DOCUMENT_REPORTED,
					RoutingKeys.WIDGET_DOCUMENT_CREATED,
					RoutingKeys.WIDGET_DOCUMENT_UPDATED,
					RoutingKeys.WIDGET_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: WidgetDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<WidgetDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.WIDGET_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_WIDGETS);

				delete this.meta[body.id];

				if (this.data && body.id in this.data) {
					const recordToDelete = this.data[body.id];

					delete this.data[body.id];

					const displayStore = useWidgetDisplay();
					const dataSourcesStore = useWidgetDataSources();

					displayStore.unset({ widget: recordToDelete });
					dataSourcesStore.unset({ widget: recordToDelete });
				}
			} else {
				if (payload.routingKey === RoutingKeys.WIDGET_DOCUMENT_UPDATED && this.semaphore.updating.includes(body.id)) {
					return true;
				}

				if (this.data && body.id in this.data) {
					const record = storeRecordFactory({
						...this.data[body.id],
						...{
							name: body.name,
							comment: body.comment,
							owner: body.owner,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
						this.data[body.id] = record;

						await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS);

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
		 * @param {IWidgetsInsertDataActionPayload} payload
		 */
		async insertData(payload: IWidgetsInsertDataActionPayload): Promise<boolean> {
			this.data = this.data ?? {};

			let documents: WidgetDocument[] = [];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<WidgetDocument>(exchangeDocumentSchema);

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
							type: doc.type,
							source: doc.source,
							entity: 'widget',
						},
						identifier: doc.identifier,
						name: doc.name,
						comment: doc.comment,
						owner: doc.owner,
					},
				});

				if (documents.length === 1) {
					this.data[doc.id] = record;
				}

				await addRecord<IWidgetDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS);

				this.meta[record.id] = record.type;
			}

			return true;
		},

		/**
		 * Load record from database
		 *
		 * @param {IWidgetsLoadRecordActionPayload} payload
		 */
		async loadRecord(payload: IWidgetsLoadRecordActionPayload): Promise<boolean> {
			const record = await getRecord<IWidgetDatabaseRecord>(payload.id, DB_TABLE_WIDGETS);

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
			const records = await getAllRecords<IWidgetDatabaseRecord>(DB_TABLE_WIDGETS);

			this.data = this.data ?? {};

			for (const record of records) {
				this.data[record.id] = storeRecordFactory(record);
			}

			return true;
		},
	},
});

export const registerWidgetsStore = (pinia: Pinia): Store => {
	return useWidgets(pinia);
};
