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

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.property.json';
import { channelsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ChannelPropertiesStoreSetup,
	ChannelPropertyDocument,
	IChannel,
	IChannelPropertiesInsertDataActionPayload,
	IChannelPropertiesLoadAllRecordsActionPayload,
	IChannelPropertiesLoadRecordActionPayload,
	IChannelPropertiesSetStateActionPayload,
	IChannelPropertiesStateSemaphore,
	IChannelPropertyDatabaseRecord,
	IChannelPropertyMeta,
	IPlainRelation,
	PropertyCategory,
	RoutingKeys,
} from '../../types';
import { PropertyType } from '../../types';
import { DB_TABLE_CHANNELS_PROPERTIES, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IChannelPropertiesActions,
	IChannelPropertiesAddActionPayload,
	IChannelPropertiesEditActionPayload,
	IChannelPropertiesFetchActionPayload,
	IChannelPropertiesGetActionPayload,
	IChannelPropertiesRemoveActionPayload,
	IChannelPropertiesResponseJson,
	IChannelPropertiesSaveActionPayload,
	IChannelPropertiesSetActionPayload,
	IChannelPropertiesSocketDataActionPayload,
	IChannelPropertiesState,
	IChannelPropertiesUnsetActionPayload,
	IChannelProperty,
	IChannelPropertyRecordFactoryPayload,
	IChannelPropertyResponseJson,
	IChannelPropertyResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IChannelPropertyRecordFactoryPayload): Promise<IChannelProperty> => {
	const channelsStore = storesManager.getStore(channelsStoreKey);

	let channel = 'channel' in data ? lodashGet(data, 'channel', null) : null;

	let channelMeta = data.channelId ? channelsStore.findMeta(data.channelId) : null;

	if (channel === null && channelMeta !== null) {
		channel = {
			id: data.channelId as string,
			type: channelMeta,
		};
	}

	if (channel === null) {
		if (!('channelId' in data)) {
			throw new Error("Channel for property couldn't be loaded from store");
		}

		if (channelsStore.findById(data.channelId as string) === null && !(await channelsStore.get({ id: data.channelId as string, refresh: false }))) {
			throw new Error("Channel for property couldn't be loaded from server");
		}

		channelMeta = channelsStore.findMeta(data.channelId as string);

		if (channelMeta === null) {
			throw new Error("Channel for property couldn't be loaded from store");
		}

		channel = {
			id: data.channelId as string,
			type: channelMeta,
		};
	}

	const record: IChannelProperty = {
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
		relationshipNames: ['channel', 'parent', 'children'],

		channel: {
			id: channel.id,
			type: channel.type,
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

const databaseRecordFactory = (record: IChannelProperty): IChannelPropertyDatabaseRecord => {
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
					id: record.channel.id,
					type: {
						type: record.channel.type.type,
						source: record.channel.type.source,
						entity: record.channel.type.entity,
						parent: record.channel.type.parent,
					},
				}
			: null,
		children: record.children.map((children) => ({
			id: children.id,
			type: { type: children.type.type, source: children.type.source, entity: children.type.entity, parent: children.type.parent },
		})),

		channel: {
			id: record.channel.id,
			type: {
				type: record.channel.type.type,
				source: record.channel.type.source,
				entity: record.channel.type.entity,
			},
		},
	};
};

export const useChannelProperties = defineStore<'devices_module_channels_properties', ChannelPropertiesStoreSetup>(
	'devices_module_channels_properties',
	(): ChannelPropertiesStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IChannelPropertiesStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IChannel['id'][]>([]);

		const data = ref<{ [key: IChannelProperty['id']]: IChannelProperty } | undefined>(undefined);

		const meta = ref<{ [key: IChannelProperty['id']]: IChannelPropertyMeta }>({});

		const firstLoadFinished = (channelId: IChannel['id']): boolean => firstLoad.value.includes(channelId);

		const getting = (id: IChannelProperty['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (channelId: IChannel['id'] | null): boolean =>
			channelId !== null ? semaphore.value.fetching.items.includes(channelId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IChannelProperty['id']): IChannelProperty | null => {
			const property: IChannelProperty | undefined = Object.values(data.value ?? {}).find(
				(property: IChannelProperty): boolean => property.id === id
			);

			return property ?? null;
		};

		const findByIdentifier = (channel: IChannel, identifier: IChannelProperty['identifier']): IChannelProperty | null => {
			const property: IChannelProperty | undefined = Object.values(data.value ?? {}).find((property: IChannelProperty): boolean => {
				return property.channel.id === channel.id && property.identifier.toLowerCase() === identifier.toLowerCase();
			});

			return property ?? null;
		};

		const findForChannel = (channelId: IChannel['id']): IChannelProperty[] =>
			Object.values(data.value ?? {}).filter((property: IChannelProperty): boolean => property.channel.id === channelId);

		const findMeta = (id: IChannelProperty['id']): IChannelPropertyMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IChannelPropertiesSetActionPayload): Promise<IChannelProperty> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_PROPERTIES);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IChannelPropertiesUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.channel !== undefined) {
				const items = findForChannel(payload.channel.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_CHANNELS_PROPERTIES);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_CHANNELS_PROPERTIES);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least channel or property id');
		};

		const get = async (payload: IChannelPropertiesGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const propertyResponse = await axios.get<IChannelPropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/properties/${payload.id}`
				);

				const propertyResponseModel = jsonApiFormatter.deserialize(propertyResponse.data) as IChannelPropertyResponseModel;

				data.value = data.value ?? {};
				data.value[propertyResponseModel.id] = await storeRecordFactory(storesManager, {
					...propertyResponseModel,
					...{ channelId: propertyResponseModel.channel.id, parentId: propertyResponseModel.parent?.id },
				});

				await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(data.value[propertyResponseModel.id]), DB_TABLE_CHANNELS_PROPERTIES);

				meta.value[propertyResponseModel.id] = propertyResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.channel-properties.get.failed', e, 'Fetching property failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IChannelPropertiesFetchActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.items.includes(payload.channel.id)) {
				return false;
			}

			const fromDatabase = await loadAllRecords({ channel: payload.channel });

			if (fromDatabase && payload?.refresh === false) {
				return true;
			}

			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items.push(payload.channel.id);
			}

			firstLoad.value = firstLoad.value.filter((item) => item !== payload.channel.id);
			firstLoad.value = [...new Set(firstLoad.value)];

			try {
				const propertiesResponse = await axios.get<IChannelPropertiesResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/properties`
				);

				const propertiesResponseModel = jsonApiFormatter.deserialize(propertiesResponse.data) as IChannelPropertyResponseModel[];

				for (const property of propertiesResponseModel) {
					data.value = data.value ?? {};
					data.value[property.id] = await storeRecordFactory(storesManager, {
						...property,
						...{ channelId: property.channel.id, parentId: property.parent?.id },
					});

					await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(data.value[property.id]), DB_TABLE_CHANNELS_PROPERTIES);

					meta.value[property.id] = property.type;
				}

				firstLoad.value.push(payload.channel.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IChannelPropertyDatabaseRecord>(DB_TABLE_CHANNELS_PROPERTIES);
				const indexedDbIds: string[] = allRecords.filter((record) => record.channel.id === payload.channel.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CHANNELS_PROPERTIES);

					delete meta.value[id];
				}
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					try {
						const channelsStore = storesManager.getStore(channelsStoreKey);

						await channelsStore.get({
							id: payload.channel.id,
						});
					} catch (e: any) {
						if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
							const channelsStore = storesManager.getStore(channelsStoreKey);

							channelsStore.unset({
								id: payload.channel.id,
							});

							return true;
						}
					}
				}

				throw new ApiError('devices-module.channel-properties.fetch.failed', e, 'Fetching properties failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.channel.id);
			}

			return true;
		};

		const add = async (payload: IChannelPropertiesAddActionPayload): Promise<IChannelProperty> => {
			const newProperty = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					category: PropertyCategory.GENERIC,
					draft: payload?.draft,
					channelId: payload.channel.id,
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
				const channelsStore = storesManager.getStore(channelsStoreKey);

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);

					throw new Error('devices-module.channel-properties.create.failed');
				}

				try {
					const apiData: Partial<IChannelProperty> =
						newProperty.parent !== null
							? {
									id: newProperty.id,
									type: newProperty.type,
									identifier: newProperty.identifier,
									name: newProperty.name,
									value: newProperty.value,
									channel: newProperty.channel,
									parent: newProperty.parent,
									relationshipNames: ['channel', 'parent'],
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
									channel: newProperty.channel,
									relationshipNames: ['channel'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const createdProperty = await axios.post<IChannelPropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/properties`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const createdPropertyModel = jsonApiFormatter.deserialize(createdProperty.data) as IChannelPropertyResponseModel;

					data.value[createdPropertyModel.id] = await storeRecordFactory(storesManager, {
						...createdPropertyModel,
						...{ channelId: createdPropertyModel.channel.id, parentId: createdPropertyModel.parent?.id },
					});

					await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(data.value[createdPropertyModel.id]), DB_TABLE_CHANNELS_PROPERTIES);

					meta.value[createdPropertyModel.id] = createdPropertyModel.type;

					return data.value[createdPropertyModel.id];
				} catch (e: any) {
					// Transformer could not be created on api, we have to remove it from database
					delete data.value[newProperty.id];

					throw new ApiError('devices-module.channel-properties.create.failed', e, 'Create new property failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newProperty.id);
				}
			}
		};

		const edit = async (payload: IChannelPropertiesEditActionPayload): Promise<IChannelProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.channel-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			const updatedRecord = {
				...existingRecord,
				...payload.data,
				...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
			} as IChannelProperty;

			data.value[payload.id] = await storeRecordFactory(storesManager, {
				...updatedRecord,
			});

			if (updatedRecord.draft) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				return data.value[payload.id];
			} else {
				const channelsStore = storesManager.getStore(channelsStoreKey);

				const channel = channelsStore.findById(updatedRecord.channel.id);

				if (channel === null) {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

					throw new Error('devices-module.channel-properties.update.failed');
				}

				try {
					const apiData: Partial<IChannelProperty> =
						updatedRecord.parent !== null
							? {
									id: updatedRecord.id,
									type: updatedRecord.type,
									identifier: updatedRecord.identifier,
									name: updatedRecord.name,
									value: updatedRecord.value,
									channel: updatedRecord.channel,
									parent: updatedRecord.parent,
									relationshipNames: ['channel', 'parent'],
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
									channel: updatedRecord.channel,
									relationshipNames: ['channel'],
								};

					if (apiData?.type?.type === PropertyType.DYNAMIC) {
						delete apiData.value;
					}

					if (apiData?.type?.type === PropertyType.VARIABLE) {
						delete apiData.settable;
						delete apiData.queryable;
					}

					const updatedProperty = await axios.patch<IChannelPropertyResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/channels/${updatedRecord.channel.id}/properties/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: apiData,
						})
					);

					const updatedPropertyModel = jsonApiFormatter.deserialize(updatedProperty.data) as IChannelPropertyResponseModel;

					data.value[updatedPropertyModel.id] = await storeRecordFactory(storesManager, {
						...updatedPropertyModel,
						...{ channelId: updatedPropertyModel.channel.id, parentId: updatedPropertyModel.parent?.id },
					});

					await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(data.value[updatedPropertyModel.id]), DB_TABLE_CHANNELS_PROPERTIES);

					meta.value[updatedPropertyModel.id] = updatedPropertyModel.type;

					return data.value[updatedPropertyModel.id];
				} catch (e: any) {
					const channelsStore = storesManager.getStore(channelsStoreKey);

					const channel = channelsStore.findById(updatedRecord.channel.id);

					if (channel !== null) {
						// Updating entity on api failed, we need to refresh entity
						await get({ channel, id: payload.id });
					}

					throw new ApiError('devices-module.channel-properties.update.failed', e, 'Edit property failed.');
				} finally {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
				}
			}
		};

		const save = async (payload: IChannelPropertiesSaveActionPayload): Promise<IChannelProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.channel-properties.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-properties.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const channelsStore = storesManager.getStore(channelsStoreKey);

			const channel = channelsStore.findById(recordToSave.channel.id);

			if (channel === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.channel-properties.save.failed');
			}

			try {
				const apiData: Partial<IChannelProperty> =
					recordToSave.parent !== null
						? {
								id: recordToSave.id,
								type: recordToSave.type,
								identifier: recordToSave.identifier,
								name: recordToSave.name,
								value: recordToSave.value,
								channel: recordToSave.channel,
								parent: recordToSave.parent,
								relationshipNames: ['channel', 'parent'],
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
								channel: recordToSave.channel,
								relationshipNames: ['channel'],
							};

				if (apiData?.type?.type === PropertyType.DYNAMIC) {
					delete apiData.value;
				}

				if (apiData?.type?.type === PropertyType.VARIABLE) {
					delete apiData.settable;
					delete apiData.queryable;
				}

				const savedProperty = await axios.post<IChannelPropertyResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/channels/${recordToSave.channel.id}/properties`,
					jsonApiFormatter.serialize({
						stuff: apiData,
					})
				);

				const savedPropertyModel = jsonApiFormatter.deserialize(savedProperty.data) as IChannelPropertyResponseModel;

				data.value[savedPropertyModel.id] = await storeRecordFactory(storesManager, {
					...savedPropertyModel,
					...{ channelId: savedPropertyModel.channel.id, parentId: savedPropertyModel.parent?.id },
				});

				await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(data.value[savedPropertyModel.id]), DB_TABLE_CHANNELS_PROPERTIES);

				meta.value[savedPropertyModel.id] = savedPropertyModel.type;

				return data.value[savedPropertyModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.channel-properties.save.failed', e, 'Save draft property failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const setState = async (payload: IChannelPropertiesSetStateActionPayload): Promise<IChannelProperty> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.channel-properties.update.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-properties.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			data.value[payload.id] = {
				...existingRecord,
				...payload.data,
				...{ parent: payload.parent ? { id: payload.parent.id, type: payload.parent.type } : existingRecord.parent },
			} as IChannelProperty;

			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		};

		const remove = async (payload: IChannelPropertiesRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.channel-properties.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-properties.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const channelsStore = storesManager.getStore(channelsStoreKey);

			const channel = channelsStore.findById(recordToDelete.channel.id);

			if (channel === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.channel-properties.delete.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_CHANNELS_PROPERTIES);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/channels/${recordToDelete.channel.id}/properties/${recordToDelete.id}`);
				} catch (e: any) {
					const channelsStore = storesManager.getStore(channelsStoreKey);

					const channel = channelsStore.findById(recordToDelete.channel.id);

					if (channel !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ channel, id: payload.id });
					}

					throw new ApiError('devices-module.channel-properties.delete.failed', e, 'Delete property failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const socketData = async (payload: IChannelPropertiesSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_REPORTED,
					RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_CREATED,
					RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_UPDATED,
					RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ChannelPropertyDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ChannelPropertyDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CHANNELS_PROPERTIES);

				delete meta.value[body.id];

				if (data.value && body.id in data.value) {
					delete data.value[body.id];
				}
			} else {
				if (payload.routingKey === RoutingKeys.CHANNEL_PROPERTY_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
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
							channelId: body.channel,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_PROPERTIES);

						meta.value[record.id] = record.type;
					}
				} else {
					const channelsStore = storesManager.getStore(channelsStoreKey);

					const channel = channelsStore.findById(body.channel);

					if (channel !== null) {
						try {
							await get({
								channel,
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

		const insertData = async (payload: IChannelPropertiesInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: ChannelPropertyDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const channelIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ChannelPropertyDocument>(exchangeDocumentSchema);

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
							parent: 'channel',
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
						channelId: doc.channel,
						parentId: doc.parent,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IChannelPropertyDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_PROPERTIES);

				meta.value[record.id] = record.type;

				channelIds.push(doc.channel);
			}

			if (documents.length > 1) {
				const uniqueChannelIds = [...new Set(channelIds)];

				for (const channelId of uniqueChannelIds) {
					firstLoad.value.push(channelId);
					firstLoad.value = [...new Set(firstLoad.value)];
				}
			}

			return true;
		};

		const loadRecord = async (payload: IChannelPropertiesLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IChannelPropertyDatabaseRecord>(payload.id, DB_TABLE_CHANNELS_PROPERTIES);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IChannelPropertiesLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IChannelPropertyDatabaseRecord>(DB_TABLE_CHANNELS_PROPERTIES);

			data.value = data.value ?? {};

			for (const record of records) {
				if (payload?.channel && payload?.channel.id !== record?.channel.id) {
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
			findForChannel,
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

export const registerChannelsPropertiesStore = (pinia: Pinia): Store<string, IChannelPropertiesState, object, IChannelPropertiesActions> => {
	return useChannelProperties(pinia);
};
