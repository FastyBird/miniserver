import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import exchangeDocumentSchema from '../../../resources/schemas/document.widget.dataSource.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useWidgets } from '../../models';
import { ActionRoutes, ExchangeCommand, RoutingKeys, WidgetDataSourceDocument } from '../../types';
import { DB_TABLE_WIDGETS_DATA_SOURCES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';
import { IWidget } from '../widgets/types';

import {
	IWidgetDataSource,
	IWidgetDataSourceDatabaseRecord,
	IWidgetDataSourceEditActionPayload,
	IWidgetDataSourceMeta,
	IWidgetDataSourceRecordFactoryPayload,
	IWidgetDataSourceResponseJson,
	IWidgetDataSourceResponseModel,
	IWidgetDataSourcesActions,
	IWidgetDataSourcesAddActionPayload,
	IWidgetDataSourcesFetchActionPayload,
	IWidgetDataSourcesGetActionPayload,
	IWidgetDataSourcesGetters,
	IWidgetDataSourcesInsertDataActionPayload,
	IWidgetDataSourcesLoadAllRecordsActionPayload,
	IWidgetDataSourcesLoadRecordActionPayload,
	IWidgetDataSourcesRemoveActionPayload,
	IWidgetDataSourcesResponseJson,
	IWidgetDataSourcesSaveActionPayload,
	IWidgetDataSourcesSetActionPayload,
	IWidgetDataSourcesSocketDataActionPayload,
	IWidgetDataSourcesState,
	IWidgetDataSourcesTransmitCommandActionPayload,
	IWidgetDataSourcesUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IWidgetDataSourceRecordFactoryPayload): Promise<IWidgetDataSource> => {
	const widgetsStore = useWidgets();

	let widget = 'widget' in data ? get(data, 'widget', null) : null;

	let widgetMeta = data.widgetId ? widgetsStore.findMeta(data.widgetId) : null;

	if (widget === null && widgetMeta !== null) {
		widget = {
			id: data.widgetId as string,
			type: widgetMeta,
		};
	}

	if (widget === null) {
		if (!('widgetId' in data)) {
			throw new Error("Widget for data source couldn't be loaded from store");
		}

		if (!(await widgetsStore.get({ id: data.widgetId as string, refresh: false }))) {
			throw new Error("Widget for data source couldn't be loaded from server");
		}

		widgetMeta = widgetsStore.findMeta(data.widgetId as string);

		if (widgetMeta === null) {
			throw new Error("Widget for data source couldn't be loaded from store");
		}

		widget = {
			id: data.widgetId as string,
			type: widgetMeta,
		};
	}

	return {
		id: get(data, 'id', uuid().toString()),
		type: data.type,

		draft: get(data, 'draft', false),

		params: get(data, 'params', {}),

		// Relations
		relationshipNames: ['widget'],

		widget: {
			id: widget.id,
			type: widget.type,
		},
	} as IWidgetDataSource;
};

const databaseRecordFactory = (record: IWidgetDataSource): IWidgetDataSourceDatabaseRecord => {
	return {
		id: record.id,
		type: {
			type: record.type.type,
			source: record.type.source,
			entity: record.type.entity,
		},

		params: record.params,

		relationshipNames: record.relationshipNames.map((name) => name),

		widget: {
			id: record.widget.id,
			type: {
				type: record.widget.type.type,
				source: record.widget.type.source,
				entity: record.widget.type.entity,
			},
		},
	};
};

export const useWidgetDataSources = defineStore<string, IWidgetDataSourcesState, IWidgetDataSourcesGetters, IWidgetDataSourcesActions>(
	'ui_module_widgets_data_sources',
	{
		state: (): IWidgetDataSourcesState => {
			return {
				semaphore: {
					fetching: {
						items: [],
						item: [],
					},
					creating: [],
					updating: [],
					deleting: [],
				},

				data: undefined,
				meta: {},
			};
		},

		getters: {
			getting: (state: IWidgetDataSourcesState): ((id: IWidgetDataSource['id']) => boolean) => {
				return (id: IWidgetDataSource['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IWidgetDataSourcesState): ((widgetId: IWidget['id'] | null) => boolean) => {
				return (widgetId: IWidget['id'] | null): boolean =>
					widgetId !== null ? state.semaphore.fetching.items.includes(widgetId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IWidgetDataSourcesState): ((id: IWidgetDataSource['id']) => IWidgetDataSource | null) => {
				return (id: IWidgetDataSource['id']): IWidgetDataSource | null => {
					const dataSource: IWidgetDataSource | undefined = Object.values(state.data ?? {}).find(
						(dataSource: IWidgetDataSource): boolean => dataSource.id === id
					);

					return dataSource ?? null;
				};
			},

			findForWidget: (state: IWidgetDataSourcesState): ((widgetId: IWidget['id']) => IWidgetDataSource[]) => {
				return (widgetId: IWidget['id']): IWidgetDataSource[] => {
					return Object.values(state.data ?? {}).filter((dataSource: IWidgetDataSource): boolean => dataSource.widget.id === widgetId);
				};
			},

			findMeta: (state: IWidgetDataSourcesState): ((id: IWidgetDataSource['id']) => IWidgetDataSourceMeta | null) => {
				return (id: IWidgetDataSource['id']): IWidgetDataSourceMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IWidgetDataSourcesSetActionPayload} payload
			 */
			async set(payload: IWidgetDataSourcesSetActionPayload): Promise<IWidgetDataSource> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IWidgetDataSourceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IWidgetDataSourcesUnsetActionPayload} payload
			 */
			async unset(payload: IWidgetDataSourcesUnsetActionPayload): Promise<void> {
				if (!this.data) {
					return;
				}

				if (payload.widget !== undefined) {
					const items = this.findForWidget(payload.widget.id);

					for (const item of items) {
						if (item.id in (this.data ?? {})) {
							await removeRecord(item.id, DB_TABLE_WIDGETS_DATA_SOURCES);

							delete this.meta[item.id];

							delete (this.data ?? {})[item.id];
						}
					}

					return;
				} else if (payload.id !== undefined) {
					await removeRecord(payload.id, DB_TABLE_WIDGETS_DATA_SOURCES);

					delete this.meta[payload.id];

					delete this.data[payload.id];

					return;
				}

				throw new Error('You have to provide at least widget or data source id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IWidgetDataSourcesGetActionPayload} payload
			 */
			async get(payload: IWidgetDataSourcesGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const dataSourceResponse = await axios.get<IWidgetDataSourceResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${payload.widget.id}/data-sources/${payload.id}`
					);

					const dataSourceResponseModel = jsonApiFormatter.deserialize(dataSourceResponse.data) as IWidgetDataSourceResponseModel;

					this.data = this.data ?? {};
					this.data[dataSourceResponseModel.id] = await storeRecordFactory({
						...dataSourceResponseModel,
						...{ widgetId: dataSourceResponseModel.widget.id },
					});

					await addRecord<IWidgetDataSourceDatabaseRecord>(
						databaseRecordFactory(this.data[dataSourceResponseModel.id]),
						DB_TABLE_WIDGETS_DATA_SOURCES
					);

					this.meta[dataSourceResponseModel.id] = dataSourceResponseModel.type;
				} catch (e: any) {
					throw new ApiError('ui-module.widget-dataSources.get.failed', e, 'Fetching data source failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Fetch all records from server
			 *
			 * @param {IWidgetDataSourcesFetchActionPayload} payload
			 */
			async fetch(payload: IWidgetDataSourcesFetchActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.items.includes(payload.widget.id)) {
					return false;
				}

				const fromDatabase = await this.loadAllRecords({ widget: payload.widget });

				if (fromDatabase && payload?.refresh === false) {
					return true;
				}

				this.semaphore.fetching.items.push(payload.widget.id);

				try {
					const dataSourcesResponse = await axios.get<IWidgetDataSourcesResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${payload.widget.id}/data-sources`
					);

					const dataSourcesResponseModel = jsonApiFormatter.deserialize(dataSourcesResponse.data) as IWidgetDataSourceResponseModel[];

					for (const dataSource of dataSourcesResponseModel) {
						this.data = this.data ?? {};
						this.data[dataSource.id] = await storeRecordFactory({
							...dataSource,
							...{ widgetId: dataSource.widget.id },
						});

						await addRecord<IWidgetDataSourceDatabaseRecord>(databaseRecordFactory(this.data[dataSource.id]), DB_TABLE_WIDGETS_DATA_SOURCES);

						this.meta[dataSource.id] = dataSource.type;
					}

					// Get all current IDs from IndexedDB
					const allRecords = await getAllRecords<IWidgetDataSourceDatabaseRecord>(DB_TABLE_WIDGETS_DATA_SOURCES);
					const indexedDbIds: string[] = allRecords.filter((record) => record.widget.id === payload.widget.id).map((record) => record.id);

					// Get the IDs from the latest changes
					const serverIds: string[] = Object.keys(this.data ?? {});

					// Find IDs that are in IndexedDB but not in the server response
					const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

					// Remove records that are no longer present on the server
					for (const id of idsToRemove) {
						await removeRecord(id, DB_TABLE_WIDGETS_DATA_SOURCES);

						delete this.meta[id];
					}
				} catch (e: any) {
					throw new ApiError('ui-module.widget-dataSources.fetch.failed', e, 'Fetching dataSources failed.');
				} finally {
					this.semaphore.fetching.items = this.semaphore.fetching.items.filter((item) => item !== payload.widget.id);
				}

				return true;
			},

			/**
			 * Add new record
			 *
			 * @param {IWidgetDataSourcesAddActionPayload} payload
			 */
			async add(payload: IWidgetDataSourcesAddActionPayload): Promise<IWidgetDataSource> {
				const newDataSource = await storeRecordFactory({
					...{
						id: payload?.id,
						type: payload?.type,
						draft: payload?.draft,
						widgetId: payload.widget.id,
					},
					...payload.data,
				});

				this.semaphore.creating.push(newDataSource.id);

				this.data = this.data ?? {};
				this.data[newDataSource.id] = newDataSource;

				if (newDataSource.draft) {
					this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDataSource.id);

					return newDataSource;
				} else {
					const widgetsStore = useWidgets();

					const widget = widgetsStore.findById(payload.widget.id);

					if (widget === null) {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDataSource.id);

						throw new Error('ui-module.widget-dataSources.get.failed');
					}

					try {
						const createdDataSource = await axios.post<IWidgetDataSourceResponseJson>(
							`/${ModulePrefix.UI}/v1/widgets/${payload.widget.id}/data-sources`,
							jsonApiFormatter.serialize({
								stuff: newDataSource,
							})
						);

						const createdDataSourceModel = jsonApiFormatter.deserialize(createdDataSource.data) as IWidgetDataSourceResponseModel;

						this.data[createdDataSourceModel.id] = await storeRecordFactory({
							...createdDataSourceModel,
							...{ widgetId: createdDataSourceModel.widget.id },
						});

						await addRecord<IWidgetDataSourceDatabaseRecord>(
							databaseRecordFactory(this.data[createdDataSourceModel.id]),
							DB_TABLE_WIDGETS_DATA_SOURCES
						);

						this.meta[createdDataSourceModel.id] = createdDataSourceModel.type;

						return this.data[createdDataSourceModel.id];
					} catch (e: any) {
						// Record could not be created on api, we have to remove it from database
						delete this.data[newDataSource.id];

						throw new ApiError('ui-module.widget-dataSources.create.failed', e, 'Create new data source failed.');
					} finally {
						this.semaphore.creating = this.semaphore.creating.filter((item) => item !== newDataSource.id);
					}
				}
			},

			/**
			 * Edit existing record
			 *
			 * @param {IWidgetDataSourceEditActionPayload} payload
			 */
			async edit(payload: IWidgetDataSourceEditActionPayload): Promise<IWidgetDataSource> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
				} as IWidgetDataSource;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					const widgetsStore = useWidgets();

					const widget = widgetsStore.findById(updatedRecord.widget.id);

					if (widget === null) {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

						throw new Error('ui-module.widget-dataSources.update.failed');
					}

					try {
						const apiData: Partial<IWidgetDataSource> = {
							id: updatedRecord.id,
							type: updatedRecord.type,
							params: updatedRecord.params,
							widget: updatedRecord.widget,
							relationshipNames: ['widget'],
						};

						const updatedDataSource = await axios.patch<IWidgetDataSourceResponseJson>(
							`/${ModulePrefix.UI}/v1/widgets/${updatedRecord.widget.id}/data-sources/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedDataSourceModel = jsonApiFormatter.deserialize(updatedDataSource.data) as IWidgetDataSourceResponseModel;

						this.data[updatedDataSourceModel.id] = await storeRecordFactory({
							...updatedDataSourceModel,
							...{ widgetId: updatedDataSourceModel.widget.id },
						});

						await addRecord<IWidgetDataSourceDatabaseRecord>(
							databaseRecordFactory(this.data[updatedDataSourceModel.id]),
							DB_TABLE_WIDGETS_DATA_SOURCES
						);

						this.meta[updatedDataSourceModel.id] = updatedDataSourceModel.type;

						return this.data[updatedDataSourceModel.id];
					} catch (e: any) {
						const widgetsStore = useWidgets();

						const widget = widgetsStore.findById(updatedRecord.widget.id);

						if (widget !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ widget, id: payload.id });
						}

						throw new ApiError('ui-module.widget-dataSources.update.failed', e, 'Edit data source failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IWidgetDataSourcesSaveActionPayload} payload
			 */
			async save(payload: IWidgetDataSourcesSaveActionPayload): Promise<IWidgetDataSource> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const widgetsStore = useWidgets();

				const widget = widgetsStore.findById(recordToSave.widget.id);

				if (widget === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('ui-module.widget-dataSources.get.failed');
				}

				try {
					const savedDataSource = await axios.post<IWidgetDataSourceResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${recordToSave.widget.id}/data-sources`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedDataSourceModel = jsonApiFormatter.deserialize(savedDataSource.data) as IWidgetDataSourceResponseModel;

					this.data[savedDataSourceModel.id] = await storeRecordFactory({
						...savedDataSourceModel,
						...{ widgetId: savedDataSourceModel.widget.id },
					});

					await addRecord<IWidgetDataSourceDatabaseRecord>(databaseRecordFactory(this.data[savedDataSourceModel.id]), DB_TABLE_WIDGETS_DATA_SOURCES);

					this.meta[savedDataSourceModel.id] = savedDataSourceModel.type;

					return this.data[savedDataSourceModel.id];
				} catch (e: any) {
					throw new ApiError('ui-module.widget-dataSources.save.failed', e, 'Save draft data source failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Remove existing record from store and server
			 *
			 * @param {IWidgetDataSourcesRemoveActionPayload} payload
			 */
			async remove(payload: IWidgetDataSourcesRemoveActionPayload): Promise<boolean> {
				if (this.semaphore.deleting.includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.delete.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.delete.failed');
				}

				this.semaphore.deleting.push(payload.id);

				const recordToDelete = this.data[payload.id];

				const widgetsStore = useWidgets();

				const widget = widgetsStore.findById(recordToDelete.widget.id);

				if (widget === null) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);

					throw new Error('ui-module.widget-dataSources.get.failed');
				}

				delete this.data[payload.id];

				await removeRecord(payload.id, DB_TABLE_WIDGETS_DATA_SOURCES);

				delete this.meta[payload.id];

				if (recordToDelete.draft) {
					this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
				} else {
					try {
						await axios.delete(`/${ModulePrefix.UI}/v1/widgets/${recordToDelete.widget.id}/data-sources/${recordToDelete.id}`);
					} catch (e: any) {
						const widgetsStore = useWidgets();

						const widget = widgetsStore.findById(recordToDelete.widget.id);

						if (widget !== null) {
							// Deleting entity on api failed, we need to refresh entity
							await this.get({ widget, id: payload.id });
						}

						throw new ApiError('ui-module.widget-dataSources.delete.failed', e, 'Delete data source failed.');
					} finally {
						this.semaphore.deleting = this.semaphore.deleting.filter((item) => item !== payload.id);
					}
				}

				return true;
			},

			/**
			 * Transmit data source command to server
			 *
			 * @param {IWidgetDataSourcesTransmitCommandActionPayload} payload
			 */
			async transmitCommand(payload: IWidgetDataSourcesTransmitCommandActionPayload): Promise<boolean> {
				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-dataSources.transmit.failed');
				}

				const dataSource = this.data[payload.id];

				const widgetsStore = useWidgets();

				const widget = widgetsStore.findById(dataSource.widget.id);

				if (widget === null) {
					throw new Error('ui-module.widget-dataSources.transmit.failed');
				}

				const { call } = useWampV1Client<{ data: string }>();

				try {
					const response = await call('', {
						routing_key: ActionRoutes.WIDGET_DATA_SOURCE,
						source: dataSource.type.source,
						data: {
							action: ExchangeCommand.SET,
							widget: widget.id,
							dataSource: dataSource.id,
							expected_value: payload.value,
						},
					});

					if (get(response.data, 'response') === 'accepted') {
						return true;
					}
				} catch {
					throw new Error('ui-module.widget-dataSources.transmit.failed');
				}

				throw new Error('ui-module.widget-dataSources.transmit.failed');
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IWidgetDataSourcesSocketDataActionPayload} payload
			 */
			async socketData(payload: IWidgetDataSourcesSocketDataActionPayload): Promise<boolean> {
				if (
					![
						RoutingKeys.WIDGET_DATA_SOURCE_DOCUMENT_REPORTED,
						RoutingKeys.WIDGET_DATA_SOURCE_DOCUMENT_CREATED,
						RoutingKeys.WIDGET_DATA_SOURCE_DOCUMENT_UPDATED,
						RoutingKeys.WIDGET_DATA_SOURCE_DOCUMENT_DELETED,
					].includes(payload.routingKey as RoutingKeys)
				) {
					return false;
				}

				const body: WidgetDataSourceDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<WidgetDataSourceDocument>(exchangeDocumentSchema);

				try {
					if (!isValid(body)) {
						return false;
					}
				} catch {
					return false;
				}

				if (payload.routingKey === RoutingKeys.WIDGET_DATA_SOURCE_DOCUMENT_DELETED) {
					await removeRecord(body.id, DB_TABLE_WIDGETS_DATA_SOURCES);

					delete this.meta[body.id];

					if (this.data && body.id in this.data) {
						delete this.data[body.id];
					}
				} else {
					if (this.data && body.id in this.data) {
						const record = await storeRecordFactory({
							...this.data[body.id],
							...{
								params: body.params,
								widgetId: body.widget,
							},
						});

						if (!isEqual(JSON.parse(JSON.stringify(this.data[body.id])), JSON.parse(JSON.stringify(record)))) {
							this.data[body.id] = record;

							await addRecord<IWidgetDataSourceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

							this.meta[record.id] = record.type;
						}
					} else {
						const widgetsStore = useWidgets();

						const widget = widgetsStore.findById(body.widget);

						if (widget !== null) {
							try {
								await this.get({
									widget,
									id: body.id,
								});
							} catch {
								return false;
							}
						}
					}
				}

				return true;
			},

			/**
			 * Insert data from SSR
			 *
			 * @param {IWidgetDataSourcesInsertDataActionPayload} payload
			 */
			async insertData(payload: IWidgetDataSourcesInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: WidgetDataSourceDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const widgetIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<WidgetDataSourceDocument>(exchangeDocumentSchema);

					try {
						if (!isValid(doc)) {
							return false;
						}
					} catch {
						return false;
					}

					const record = await storeRecordFactory({
						...this.data[doc.id],
						...{
							id: doc.id,
							type: {
								type: doc.type,
								source: doc.source,
								entity: 'data-source',
							},
							params: doc.params,
							widgetId: doc.widget,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IWidgetDataSourceDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

					this.meta[record.id] = record.type;

					widgetIds.push(doc.widget);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IWidgetDataSourcesLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IWidgetDataSourcesLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IWidgetDataSourceDatabaseRecord>(payload.id, DB_TABLE_WIDGETS_DATA_SOURCES);

				if (record) {
					this.data = this.data ?? {};
					this.data[payload.id] = await storeRecordFactory(record);

					return true;
				}

				return false;
			},

			/**
			 * Load records from database
			 *
			 * @param {IWidgetDataSourcesLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IWidgetDataSourcesLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IWidgetDataSourceDatabaseRecord>(DB_TABLE_WIDGETS_DATA_SOURCES);

				this.data = this.data ?? {};

				for (const record of records) {
					if (payload?.widget && payload?.widget.id !== record?.widget.id) {
						continue;
					}

					this.data[record.id] = await storeRecordFactory(record);
				}

				return true;
			},
		},
	}
);

export const registerWidgetDataSourcesStore = (pinia: Pinia): Store => {
	return useWidgetDataSources(pinia);
};
