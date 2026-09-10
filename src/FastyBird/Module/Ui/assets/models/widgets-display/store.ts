import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import get from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/metadata-library';

import exchangeDocumentSchema from '../../../resources/schemas/document.widget.display.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { useWidgets } from '../../models';
import { RoutingKeys, WidgetDisplayDocument } from '../../types';
import { DB_TABLE_WIDGETS_DATA_SOURCES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities/database';
import { IWidget } from '../widgets/types';

import {
	IWidgetDisplay,
	IWidgetDisplayActions,
	IWidgetDisplayDatabaseRecord,
	IWidgetDisplayEditActionPayload,
	IWidgetDisplayGetActionPayload,
	IWidgetDisplayGetters,
	IWidgetDisplayInsertDataActionPayload,
	IWidgetDisplayLoadAllRecordsActionPayload,
	IWidgetDisplayLoadRecordActionPayload,
	IWidgetDisplayMeta,
	IWidgetDisplayRecordFactoryPayload,
	IWidgetDisplayResponseJson,
	IWidgetDisplayResponseModel,
	IWidgetDisplaySaveActionPayload,
	IWidgetDisplaySetActionPayload,
	IWidgetDisplaySocketDataActionPayload,
	IWidgetDisplayState,
	IWidgetDisplayUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (data: IWidgetDisplayRecordFactoryPayload): Promise<IWidgetDisplay> => {
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
			throw new Error("Widget for display couldn't be loaded from store");
		}

		if (!(await widgetsStore.get({ id: data.widgetId as string, refresh: false }))) {
			throw new Error("Widget for display couldn't be loaded from server");
		}

		widgetMeta = widgetsStore.findMeta(data.widgetId as string);

		if (widgetMeta === null) {
			throw new Error("Widget for display couldn't be loaded from store");
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
	} as IWidgetDisplay;
};

const databaseRecordFactory = (record: IWidgetDisplay): IWidgetDisplayDatabaseRecord => {
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

export const useWidgetDisplay = defineStore<string, IWidgetDisplayState, IWidgetDisplayGetters, IWidgetDisplayActions>(
	'ui_module_widgets_data_sources',
	{
		state: (): IWidgetDisplayState => {
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
			getting: (state: IWidgetDisplayState): ((id: IWidgetDisplay['id']) => boolean) => {
				return (id: IWidgetDisplay['id']): boolean => state.semaphore.fetching.item.includes(id);
			},

			fetching: (state: IWidgetDisplayState): ((widgetId: IWidget['id'] | null) => boolean) => {
				return (widgetId: IWidget['id'] | null): boolean =>
					widgetId !== null ? state.semaphore.fetching.items.includes(widgetId) : state.semaphore.fetching.items.length > 0;
			},

			findById: (state: IWidgetDisplayState): ((id: IWidgetDisplay['id']) => IWidgetDisplay | null) => {
				return (id: IWidgetDisplay['id']): IWidgetDisplay | null => {
					const display: IWidgetDisplay | undefined = Object.values(state.data ?? {}).find((display: IWidgetDisplay): boolean => display.id === id);

					return display ?? null;
				};
			},

			findForWidget: (state: IWidgetDisplayState): ((widgetId: IWidget['id']) => IWidgetDisplay[]) => {
				return (widgetId: IWidget['id']): IWidgetDisplay[] => {
					return Object.values(state.data ?? {}).filter((display: IWidgetDisplay): boolean => display.widget.id === widgetId);
				};
			},

			findMeta: (state: IWidgetDisplayState): ((id: IWidgetDisplay['id']) => IWidgetDisplayMeta | null) => {
				return (id: IWidgetDisplay['id']): IWidgetDisplayMeta | null => {
					return id in state.meta ? state.meta[id] : null;
				};
			},
		},

		actions: {
			/**
			 * Set record from via other store
			 *
			 * @param {IWidgetDisplaySetActionPayload} payload
			 */
			async set(payload: IWidgetDisplaySetActionPayload): Promise<IWidgetDisplay> {
				if (this.data && payload.data.id && payload.data.id in this.data) {
					const record = await storeRecordFactory({ ...this.data[payload.data.id], ...payload.data });

					return (this.data[record.id] = record);
				}

				const record = await storeRecordFactory(payload.data);

				await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

				this.meta[record.id] = record.type;

				this.data = this.data ?? {};
				return (this.data[record.id] = record);
			},

			/**
			 * Remove records for given relation or record by given identifier
			 *
			 * @param {IWidgetDisplayUnsetActionPayload} payload
			 */
			async unset(payload: IWidgetDisplayUnsetActionPayload): Promise<void> {
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

				throw new Error('You have to provide at least widget or display id');
			},

			/**
			 * Get one record from server
			 *
			 * @param {IWidgetDisplayGetActionPayload} payload
			 */
			async get(payload: IWidgetDisplayGetActionPayload): Promise<boolean> {
				if (this.semaphore.fetching.item.includes(payload.id)) {
					return false;
				}

				const fromDatabase = await this.loadRecord({ id: payload.id });

				if (fromDatabase && payload.refresh === false) {
					return true;
				}

				this.semaphore.fetching.item.push(payload.id);

				try {
					const displayResponse = await axios.get<IWidgetDisplayResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${payload.widget.id}/displays/${payload.id}`
					);

					const displayResponseModel = jsonApiFormatter.deserialize(displayResponse.data) as IWidgetDisplayResponseModel;

					this.data = this.data ?? {};
					this.data[displayResponseModel.id] = await storeRecordFactory({
						...displayResponseModel,
						...{ widgetId: displayResponseModel.widget.id },
					});

					await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(this.data[displayResponseModel.id]), DB_TABLE_WIDGETS_DATA_SOURCES);

					this.meta[displayResponseModel.id] = displayResponseModel.type;
				} catch (e: any) {
					throw new ApiError('ui-module.widget-displays.get.failed', e, 'Fetching display failed.');
				} finally {
					this.semaphore.fetching.item = this.semaphore.fetching.item.filter((item) => item !== payload.id);
				}

				return true;
			},

			/**
			 * Edit existing record
			 *
			 * @param {IWidgetDisplayEditActionPayload} payload
			 */
			async edit(payload: IWidgetDisplayEditActionPayload): Promise<IWidgetDisplay> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('ui-module.widget-displays.update.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-displays.update.failed');
				}

				this.semaphore.updating.push(payload.id);

				// Get record stored in database
				const existingRecord = this.data[payload.id];
				// Update with new values
				const updatedRecord = {
					...existingRecord,
					...payload.data,
				} as IWidgetDisplay;

				this.data[payload.id] = updatedRecord;

				if (updatedRecord.draft) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					return this.data[payload.id];
				} else {
					const widgetsStore = useWidgets();

					const widget = widgetsStore.findById(updatedRecord.widget.id);

					if (widget === null) {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

						throw new Error('ui-module.widget-displays.update.failed');
					}

					try {
						const apiData: Partial<IWidgetDisplay> = {
							id: updatedRecord.id,
							type: updatedRecord.type,
							params: updatedRecord.params,
							widget: updatedRecord.widget,
							relationshipNames: ['widget'],
						};

						const updatedDisplay = await axios.patch<IWidgetDisplayResponseJson>(
							`/${ModulePrefix.UI}/v1/widgets/${updatedRecord.widget.id}/displays/${updatedRecord.id}`,
							jsonApiFormatter.serialize({
								stuff: apiData,
							})
						);

						const updatedDisplayModel = jsonApiFormatter.deserialize(updatedDisplay.data) as IWidgetDisplayResponseModel;

						this.data[updatedDisplayModel.id] = await storeRecordFactory({
							...updatedDisplayModel,
							...{ widgetId: updatedDisplayModel.widget.id },
						});

						await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(this.data[updatedDisplayModel.id]), DB_TABLE_WIDGETS_DATA_SOURCES);

						this.meta[updatedDisplayModel.id] = updatedDisplayModel.type;

						return this.data[updatedDisplayModel.id];
					} catch (e: any) {
						const widgetsStore = useWidgets();

						const widget = widgetsStore.findById(updatedRecord.widget.id);

						if (widget !== null) {
							// Updating entity on api failed, we need to refresh entity
							await this.get({ widget, id: payload.id });
						}

						throw new ApiError('ui-module.widget-displays.update.failed', e, 'Edit display failed.');
					} finally {
						this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
					}
				}
			},

			/**
			 * Save draft record on server
			 *
			 * @param {IWidgetDisplaySaveActionPayload} payload
			 */
			async save(payload: IWidgetDisplaySaveActionPayload): Promise<IWidgetDisplay> {
				if (this.semaphore.updating.includes(payload.id)) {
					throw new Error('ui-module.widget-displays.save.inProgress');
				}

				if (!this.data || !Object.keys(this.data).includes(payload.id)) {
					throw new Error('ui-module.widget-displays.save.failed');
				}

				this.semaphore.updating.push(payload.id);

				const recordToSave = this.data[payload.id];

				const widgetsStore = useWidgets();

				const widget = widgetsStore.findById(recordToSave.widget.id);

				if (widget === null) {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);

					throw new Error('ui-module.widget-displays.get.failed');
				}

				try {
					const savedDisplay = await axios.post<IWidgetDisplayResponseJson>(
						`/${ModulePrefix.UI}/v1/widgets/${recordToSave.widget.id}/displays`,
						jsonApiFormatter.serialize({
							stuff: recordToSave,
						})
					);

					const savedDisplayModel = jsonApiFormatter.deserialize(savedDisplay.data) as IWidgetDisplayResponseModel;

					this.data[savedDisplayModel.id] = await storeRecordFactory({
						...savedDisplayModel,
						...{ widgetId: savedDisplayModel.widget.id },
					});

					await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(this.data[savedDisplayModel.id]), DB_TABLE_WIDGETS_DATA_SOURCES);

					this.meta[savedDisplayModel.id] = savedDisplayModel.type;

					return this.data[savedDisplayModel.id];
				} catch (e: any) {
					throw new ApiError('ui-module.widget-displays.save.failed', e, 'Save draft display failed.');
				} finally {
					this.semaphore.updating = this.semaphore.updating.filter((item) => item !== payload.id);
				}
			},

			/**
			 * Receive data from sockets
			 *
			 * @param {IWidgetDisplaySocketDataActionPayload} payload
			 */
			async socketData(payload: IWidgetDisplaySocketDataActionPayload): Promise<boolean> {
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

				const body: WidgetDisplayDocument = JSON.parse(payload.data);

				const isValid = jsonSchemaValidator.compile<WidgetDisplayDocument>(exchangeDocumentSchema);

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

							await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

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
			 * @param {IWidgetDisplayInsertDataActionPayload} payload
			 */
			async insertData(payload: IWidgetDisplayInsertDataActionPayload) {
				this.data = this.data ?? {};

				let documents: WidgetDisplayDocument[] = [];

				if (Array.isArray(payload.data)) {
					documents = payload.data;
				} else {
					documents = [payload.data];
				}

				const widgetIds = [];

				for (const doc of documents) {
					const isValid = jsonSchemaValidator.compile<WidgetDisplayDocument>(exchangeDocumentSchema);

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
								entity: 'display',
							},
							params: doc.params,
							widgetId: doc.widget,
						},
					});

					if (documents.length === 1) {
						this.data[doc.id] = record;
					}

					await addRecord<IWidgetDisplayDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_WIDGETS_DATA_SOURCES);

					this.meta[record.id] = record.type;

					widgetIds.push(doc.widget);
				}

				return true;
			},

			/**
			 * Load record from database
			 *
			 * @param {IWidgetDisplayLoadRecordActionPayload} payload
			 */
			async loadRecord(payload: IWidgetDisplayLoadRecordActionPayload): Promise<boolean> {
				const record = await getRecord<IWidgetDisplayDatabaseRecord>(payload.id, DB_TABLE_WIDGETS_DATA_SOURCES);

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
			 * @param {IWidgetDisplayLoadAllRecordsActionPayload} payload
			 */
			async loadAllRecords(payload?: IWidgetDisplayLoadAllRecordsActionPayload): Promise<boolean> {
				const records = await getAllRecords<IWidgetDisplayDatabaseRecord>(DB_TABLE_WIDGETS_DATA_SOURCES);

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

export const registerWidgetDisplayStore = (pinia: Pinia): Store => {
	return useWidgetDisplay(pinia);
};
