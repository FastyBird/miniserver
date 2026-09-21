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

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.control.json';
import { channelsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ActionRoutes,
	ChannelControlDocument,
	ChannelControlsStoreSetup,
	ExchangeCommand,
	IChannel,
	IChannelControlsStateSemaphore,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_CHANNELS_CONTROLS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IChannelControl,
	IChannelControlDatabaseRecord,
	IChannelControlMeta,
	IChannelControlRecordFactoryPayload,
	IChannelControlResponseJson,
	IChannelControlResponseModel,
	IChannelControlsActions,
	IChannelControlsAddActionPayload,
	IChannelControlsFetchActionPayload,
	IChannelControlsGetActionPayload,
	IChannelControlsInsertDataActionPayload,
	IChannelControlsLoadAllRecordsActionPayload,
	IChannelControlsLoadRecordActionPayload,
	IChannelControlsRemoveActionPayload,
	IChannelControlsResponseJson,
	IChannelControlsSaveActionPayload,
	IChannelControlsSetActionPayload,
	IChannelControlsSocketDataActionPayload,
	IChannelControlsState,
	IChannelControlsTransmitCommandActionPayload,
	IChannelControlsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IChannelControlRecordFactoryPayload): Promise<IChannelControl> => {
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
			throw new Error("Channel for control couldn't be loaded from store");
		}

		if (!(await channelsStore.get({ id: data.channelId as string, refresh: false }))) {
			throw new Error("Channel for control couldn't be loaded from server");
		}

		channelMeta = channelsStore.findMeta(data.channelId as string);

		if (channelMeta === null) {
			throw new Error("Channel for control couldn't be loaded from store");
		}

		channel = {
			id: data.channelId as string,
			type: channelMeta,
		};
	}

	return {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		name: data.name,

		// Relations
		relationshipNames: ['channel'],

		channel: {
			id: channel.id,
			type: channel.type,
		},
	} as IChannelControl;
};

const databaseRecordFactory = (record: IChannelControl): IChannelControlDatabaseRecord => {
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

export const useChannelControls = defineStore<'devices_module_channels_controls', ChannelControlsStoreSetup>(
	'devices_module_channels_controls',
	(): ChannelControlsStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IChannelControlsStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IChannel['id'][]>([]);

		const data = ref<{ [key: IChannelControl['id']]: IChannelControl } | undefined>(undefined);

		const meta = ref<{ [key: IChannelControl['id']]: IChannelControlMeta }>({});

		const firstLoadFinished = (channelId: IChannel['id']): boolean => firstLoad.value.includes(channelId);

		const getting = (id: IChannelControl['id']): boolean => semaphore.value.fetching.item.includes(id);

		const fetching = (channelId: IChannel['id'] | null): boolean =>
			channelId !== null ? semaphore.value.fetching.items.includes(channelId) : semaphore.value.fetching.items.length > 0;

		const findById = (id: IChannelControl['id']): IChannelControl | null => {
			const control: IChannelControl | undefined = Object.values(data.value ?? {}).find((control: IChannelControl): boolean => control.id === id);

			return control ?? null;
		};

		const findByName = (channel: IChannel, name: IChannelControl['name']): IChannelControl | null => {
			const control: IChannelControl | undefined = Object.values(data.value ?? {}).find((control: IChannelControl): boolean => {
				return control.channel.id === channel.id && control.name.toLowerCase() === name.toLowerCase();
			});

			return control ?? null;
		};

		const findForChannel = (channelId: IChannel['id']): IChannelControl[] =>
			Object.values(data.value ?? {}).filter((control: IChannelControl): boolean => control.channel.id === channelId);

		const findMeta = (id: IChannelControl['id']): IChannelControlMeta | null => (id in meta.value ? meta.value[id] : null);

		const set = async (payload: IChannelControlsSetActionPayload): Promise<IChannelControl> => {
			if (data.value && payload.data.id && payload.data.id in data.value) {
				const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

				return (data.value[record.id] = record);
			}

			const record = await storeRecordFactory(storesManager, payload.data);

			await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

			meta.value[record.id] = record.type;

			data.value = data.value ?? {};
			return (data.value[record.id] = record);
		};

		const unset = async (payload: IChannelControlsUnsetActionPayload): Promise<void> => {
			if (!data.value) {
				return;
			}

			if (payload.channel !== undefined) {
				const items = findForChannel(payload.channel.id);

				for (const item of items) {
					if (item.id in (data.value ?? {})) {
						await removeRecord(item.id, DB_TABLE_CHANNELS_CONTROLS);

						delete meta.value[item.id];

						delete (data.value ?? {})[item.id];
					}
				}

				return;
			} else if (payload.id !== undefined) {
				await removeRecord(payload.id, DB_TABLE_CHANNELS_CONTROLS);

				delete meta.value[payload.id];

				delete data.value[payload.id];

				return;
			}

			throw new Error('You have to provide at least channel or control id');
		};

		const get = async (payload: IChannelControlsGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			const fromDatabase = await loadRecord({ id: payload.id });

			if (fromDatabase && payload.refresh === false) {
				return true;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const controlResponse = await axios.get<IChannelControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/controls/${payload.id}`
				);

				const controlResponseModel = jsonApiFormatter.deserialize(controlResponse.data) as IChannelControlResponseModel;

				data.value = data.value ?? {};
				data.value[controlResponseModel.id] = await storeRecordFactory(storesManager, {
					...controlResponseModel,
					...{ channelId: controlResponseModel.channel.id },
				});

				await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(data.value[controlResponseModel.id]), DB_TABLE_CHANNELS_CONTROLS);

				meta.value[controlResponseModel.id] = controlResponseModel.type;
			} catch (e: any) {
				if (e instanceof AxiosError && e.status === 404) {
					await unset({
						id: payload.id,
					});

					return true;
				}

				throw new ApiError('devices-module.channel-controls.get.failed', e, 'Fetching control failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IChannelControlsFetchActionPayload): Promise<boolean> => {
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
				const controlsResponse = await axios.get<IChannelControlsResponseJson>(`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/controls`);

				const controlsResponseModel = jsonApiFormatter.deserialize(controlsResponse.data) as IChannelControlResponseModel[];

				for (const control of controlsResponseModel) {
					data.value = data.value ?? {};
					data.value[control.id] = await storeRecordFactory(storesManager, {
						...control,
						...{ channelId: control.channel.id },
					});

					await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(data.value[control.id]), DB_TABLE_CHANNELS_CONTROLS);

					meta.value[control.id] = control.type;
				}

				firstLoad.value.push(payload.channel.id);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IChannelControlDatabaseRecord>(DB_TABLE_CHANNELS_CONTROLS);
				const indexedDbIds: string[] = allRecords.filter((record) => record.channel.id === payload.channel.id).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CHANNELS_CONTROLS);

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

				throw new ApiError('devices-module.channel-controls.fetch.failed', e, 'Fetching controls failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.channel.id);
			}

			return true;
		};

		const add = async (payload: IChannelControlsAddActionPayload): Promise<IChannelControl> => {
			const newControl = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					channelId: payload.channel.id,
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
				const channelsStore = storesManager.getStore(channelsStoreKey);

				const channel = channelsStore.findById(payload.channel.id);

				if (channel === null) {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);

					throw new Error('devices-module.channel-controls.get.failed');
				}

				try {
					const createdControl = await axios.post<IChannelControlResponseJson>(
						`/${ModulePrefix.DEVICES}/v1/channels/${payload.channel.id}/controls`,
						jsonApiFormatter.serialize({
							stuff: newControl,
						})
					);

					const createdControlModel = jsonApiFormatter.deserialize(createdControl.data) as IChannelControlResponseModel;

					data.value[createdControlModel.id] = await storeRecordFactory(storesManager, {
						...createdControlModel,
						...{ channelId: createdControlModel.channel.id },
					});

					await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(data.value[createdControlModel.id]), DB_TABLE_CHANNELS_CONTROLS);

					meta.value[createdControlModel.id] = createdControlModel.type;

					return data.value[createdControlModel.id];
				} catch (e: any) {
					// Record could not be created on api, we have to remove it from database
					delete data.value[newControl.id];

					throw new ApiError('devices-module.channel-controls.create.failed', e, 'Create new control failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newControl.id);
				}
			}
		};

		const save = async (payload: IChannelControlsSaveActionPayload): Promise<IChannelControl> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('devices-module.channel-controls.save.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-controls.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			const channelsStore = storesManager.getStore(channelsStoreKey);

			const channel = channelsStore.findById(recordToSave.channel.id);

			if (channel === null) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				throw new Error('devices-module.channel-controls.get.failed');
			}

			try {
				const savedControl = await axios.post<IChannelControlResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/channels/${recordToSave.channel.id}/controls`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedControlModel = jsonApiFormatter.deserialize(savedControl.data) as IChannelControlResponseModel;

				data.value[savedControlModel.id] = await storeRecordFactory(storesManager, {
					...savedControlModel,
					...{ channelId: savedControlModel.channel.id },
				});

				await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(data.value[savedControlModel.id]), DB_TABLE_CHANNELS_CONTROLS);

				meta.value[savedControlModel.id] = savedControlModel.type;

				return data.value[savedControlModel.id];
			} catch (e: any) {
				throw new ApiError('devices-module.channel-controls.save.failed', e, 'Save draft control failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const remove = async (payload: IChannelControlsRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('devices-module.channel-controls.delete.inProgress');
			}

			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-controls.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			const channelsStore = storesManager.getStore(channelsStoreKey);

			const channel = channelsStore.findById(recordToDelete.channel.id);

			if (channel === null) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

				throw new Error('devices-module.channel-controls.get.failed');
			}

			delete data.value[payload.id];

			await removeRecord(payload.id, DB_TABLE_CHANNELS_CONTROLS);

			delete meta.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.DEVICES}/v1/channels/${recordToDelete.channel.id}/controls/${recordToDelete.id}`);
				} catch (e: any) {
					const channelsStore = storesManager.getStore(channelsStoreKey);

					const channel = channelsStore.findById(recordToDelete.channel.id);

					if (channel !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ channel, id: payload.id });
					}

					throw new ApiError('devices-module.channel-controls.delete.failed', e, 'Delete control failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const transmitCommand = async (payload: IChannelControlsTransmitCommandActionPayload): Promise<boolean> => {
			if (!data.value || !Object.keys(data.value).includes(payload.id)) {
				throw new Error('devices-module.channel-controls.transmit.failed');
			}

			const control = data.value[payload.id];

			const channelsStore = storesManager.getStore(channelsStoreKey);

			const channel = channelsStore.findById(control.channel.id);

			if (channel === null) {
				throw new Error('devices-module.channel-controls.transmit.failed');
			}

			const { call } = useWampV1Client<{ data: string }>();

			try {
				const response = await call('', {
					routing_key: ActionRoutes.CHANNEL_CONTROL,
					source: control.type.source,
					data: {
						action: ExchangeCommand.SET,
						channel: channel.id,
						control: control.id,
						expected_value: payload.value,
					},
				});

				if (lodashGet(response.data, 'response') === 'accepted') {
					return true;
				}
			} catch {
				throw new Error('devices-module.channel-controls.transmit.failed');
			}

			throw new Error('devices-module.channel-controls.transmit.failed');
		};

		const socketData = async (payload: IChannelControlsSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.CHANNEL_CONTROL_DOCUMENT_REPORTED,
					RoutingKeys.CHANNEL_CONTROL_DOCUMENT_CREATED,
					RoutingKeys.CHANNEL_CONTROL_DOCUMENT_UPDATED,
					RoutingKeys.CHANNEL_CONTROL_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: ChannelControlDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<ChannelControlDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (payload.routingKey === RoutingKeys.CHANNEL_CONTROL_DOCUMENT_DELETED) {
				await removeRecord(body.id, DB_TABLE_CHANNELS_CONTROLS);

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
							channelId: body.channel,
						},
					});

					if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
						data.value[body.id] = record;

						await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

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

		const insertData = async (payload: IChannelControlsInsertDataActionPayload): Promise<boolean> => {
			data.value = data.value ?? {};

			let documents: ChannelControlDocument[];

			if (Array.isArray(payload.data)) {
				documents = payload.data;
			} else {
				documents = [payload.data];
			}

			const channelIds = [];

			for (const doc of documents) {
				const isValid = jsonSchemaValidator.compile<ChannelControlDocument>(exchangeDocumentSchema);

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
							entity: 'control',
						},
						name: doc.name,
						channelId: doc.channel,
					},
				});

				if (documents.length === 1) {
					data.value[doc.id] = record;
				}

				await addRecord<IChannelControlDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS_CONTROLS);

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

		const loadRecord = async (payload: IChannelControlsLoadRecordActionPayload): Promise<boolean> => {
			const record = await getRecord<IChannelControlDatabaseRecord>(payload.id, DB_TABLE_CHANNELS_CONTROLS);

			if (record) {
				data.value = data.value ?? {};
				data.value[payload.id] = await storeRecordFactory(storesManager, record);

				return true;
			}

			return false;
		};

		const loadAllRecords = async (payload?: IChannelControlsLoadAllRecordsActionPayload): Promise<boolean> => {
			const records = await getAllRecords<IChannelControlDatabaseRecord>(DB_TABLE_CHANNELS_CONTROLS);

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
			findByName,
			findForChannel,
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

export const registerChannelsControlsStore = (pinia: Pinia): Store<string, IChannelControlsState, object, IChannelControlsActions> => {
	return useChannelControls(pinia);
};
