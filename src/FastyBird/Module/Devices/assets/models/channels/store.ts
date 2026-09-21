import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios, { AxiosError, AxiosResponse } from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import isEqual from 'lodash.isequal';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.channel.json';
import { channelControlsStoreKey, channelPropertiesStoreKey, devicesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	ChannelCategory,
	ChannelDocument,
	ChannelsStoreSetup,
	IChannelControlResponseModel,
	IChannelMeta,
	IChannelPropertyResponseModel,
	IChannelsInsertDataActionPayload,
	IChannelsLoadAllRecordsActionPayload,
	IChannelsLoadRecordActionPayload,
	IChannelsStateSemaphore,
	IDevice,
	IPlainRelation,
	RoutingKeys,
} from '../../types';
import { DB_TABLE_CHANNELS, addRecord, getAllRecords, getRecord, removeRecord } from '../../utilities';

import {
	IChannel,
	IChannelDatabaseRecord,
	IChannelRecordFactoryPayload,
	IChannelResponseJson,
	IChannelResponseModel,
	IChannelsActions,
	IChannelsAddActionPayload,
	IChannelsEditActionPayload,
	IChannelsFetchActionPayload,
	IChannelsGetActionPayload,
	IChannelsRemoveActionPayload,
	IChannelsResponseJson,
	IChannelsSaveActionPayload,
	IChannelsSetActionPayload,
	IChannelsSocketDataActionPayload,
	IChannelsState,
	IChannelsUnsetActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, data: IChannelRecordFactoryPayload): Promise<IChannel> => {
	const devicesStore = storesManager.getStore(devicesStoreKey);

	let device = 'device' in data ? lodashGet(data, 'device', null) : null;

	let deviceMeta = data.deviceId ? devicesStore.findMeta(data.deviceId) : null;

	if (device === null && deviceMeta !== null) {
		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	if (device === null) {
		if (!('deviceId' in data)) {
			throw new Error("Device for channel couldn't be loaded from store");
		}

		if (!(await devicesStore.get({ id: data.deviceId as string, refresh: false }))) {
			throw new Error("Device for channel couldn't be loaded from server");
		}

		deviceMeta = devicesStore.findMeta(data.deviceId as string);

		if (deviceMeta === null) {
			throw new Error("Device for channel couldn't be loaded from store");
		}

		device = {
			id: data.deviceId as string,
			type: deviceMeta,
		};
	}

	const record: IChannel = {
		id: lodashGet(data, 'id', uuid().toString()),
		type: data.type,

		draft: lodashGet(data, 'draft', false),

		category: data.category,
		identifier: data.identifier,
		name: lodashGet(data, 'name', null),
		comment: lodashGet(data, 'comment', null),

		// Relations
		relationshipNames: ['device', 'properties', 'controls'],

		controls: [],
		properties: [],

		device: {
			id: device.id,
			type: device.type,
		},

		get hasComment(): boolean {
			return this.comment !== null && this.comment !== '';
		},

		get title(): string {
			return this.name ?? this.identifier.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'properties' || relationName === 'controls') {
			lodashGet(data, relationName, []).forEach((relation: any): void => {
				if (lodashGet(relation, 'id', null) !== null && lodashGet(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: lodashGet(relation, 'id', null),
						type: lodashGet(relation, 'type', null),
					});
				}
			});
		}
	});

	return record;
};

const databaseRecordFactory = (record: IChannel): IChannelDatabaseRecord => {
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

		relationshipNames: record.relationshipNames.map((name) => name),

		controls: record.controls.map((control) => ({
			id: control.id,
			type: { type: control.type.type, source: control.type.source, entity: control.type.entity, parent: control.type.parent },
		})),
		properties: record.properties.map((property) => ({
			id: property.id,
			type: { type: property.type.type, source: property.type.source, entity: property.type.entity, parent: property.type.parent },
		})),

		device: {
			id: record.device.id,
			type: {
				type: record.device.type.type,
				source: record.device.type.source,
				entity: record.device.type.entity,
			},
		},
	};
};

const addPropertiesRelations = async (
	storesManager: IStoresManager,
	channel: IChannel,
	properties: (IChannelPropertyResponseModel | IPlainRelation)[]
): Promise<void> => {
	const propertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	for (const property of properties) {
		if ('identifier' in property) {
			await propertiesStore.set({
				data: {
					...property,
					...{
						channelId: channel.id,
					},
				},
			});
		}
	}
};

const addControlsRelations = async (
	storesManager: IStoresManager,
	channel: IChannel,
	controls: (IChannelControlResponseModel | IPlainRelation)[]
): Promise<void> => {
	const controlsStore = storesManager.getStore(channelControlsStoreKey);

	for (const control of controls) {
		if ('identifier' in control) {
			await controlsStore.set({
				data: {
					...control,
					...{
						channelId: channel.id,
					},
				},
			});
		}
	}
};

export const useChannels = defineStore<'devices_module_channels', ChannelsStoreSetup>('devices_module_channels', (): ChannelsStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IChannelsStateSemaphore>({
		fetching: {
			items: [],
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<IDevice['id'][]>([]);

	const data = ref<{ [key: IChannel['id']]: IChannel } | undefined>(undefined);

	const meta = ref<{ [key: IChannel['id']]: IChannelMeta }>({});

	const firstLoadFinished = (deviceId?: IDevice['id'] | null): boolean =>
		deviceId !== null && typeof deviceId !== 'undefined' ? firstLoad.value.includes(deviceId) : firstLoad.value.includes('all');

	const getting = (id: IChannel['id']): boolean => semaphore.value.fetching.item.includes(id);

	const fetching = (deviceId?: IDevice['id'] | null): boolean =>
		deviceId !== null && typeof deviceId !== 'undefined'
			? semaphore.value.fetching.items.includes(deviceId)
			: semaphore.value.fetching.items.includes('all');

	const findById = (id: IChannel['id']): IChannel | null => {
		const channel: IChannel | undefined = Object.values(data.value ?? {}).find((channel: IChannel): boolean => channel.id === id);

		return channel ?? null;
	};

	const findForDevice = (deviceId: string): IChannel[] =>
		Object.values(data.value ?? {}).filter((channel: IChannel): boolean => channel.device.id === deviceId);

	const findAll = (): IChannel[] => Object.values(data.value ?? {});

	const findMeta = (id: IChannel['id']): IChannelMeta | null => (id in meta.value ? meta.value[id] : null);

	const set = async (payload: IChannelsSetActionPayload): Promise<IChannel> => {
		if (payload.data.id && data.value && payload.data.id in data.value) {
			const record = await storeRecordFactory(storesManager, { ...data.value[payload.data.id], ...payload.data });

			if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
				await addPropertiesRelations(storesManager, record, payload.data.properties);
			}

			if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
				await addControlsRelations(storesManager, record, payload.data.controls);
			}

			return (data.value[record.id] = record);
		}

		const record = await storeRecordFactory(storesManager, payload.data);

		if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
			await addPropertiesRelations(storesManager, record, payload.data.properties);
		}

		if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
			await addControlsRelations(storesManager, record, payload.data.controls);
		}

		await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

		meta.value[record.id] = record.type;

		data.value = data.value ?? {};
		return (data.value[record.id] = record);
	};

	const unset = async (payload: IChannelsUnsetActionPayload): Promise<void> => {
		if (!data.value) {
			return;
		}

		if (payload.device !== undefined) {
			const items = findForDevice(payload.device.id);

			for (const item of items) {
				if (item.id in (data.value ?? {})) {
					await removeRecord(item.id, DB_TABLE_CHANNELS);

					delete meta.value[item.id];

					delete (data.value ?? {})[item.id];
				}
			}

			return;
		} else if (payload.id !== undefined) {
			await removeRecord(payload.id, DB_TABLE_CHANNELS);

			delete meta.value[payload.id];

			delete data.value[payload.id];

			return;
		}

		throw new Error('You have to provide at least device or channel id');
	};

	const get = async (payload: IChannelsGetActionPayload): Promise<boolean> => {
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
			let channelResponse: AxiosResponse<IChannelResponseJson>;

			if (payload.deviceId) {
				channelResponse = await axios.get<IChannelResponseJson>(`/${ModulePrefix.DEVICES}/v1/devices/${payload.deviceId}/channels/${payload.id}`);
			} else {
				channelResponse = await axios.get<IChannelResponseJson>(`/${ModulePrefix.DEVICES}/v1/channels/${payload.id}`);
			}

			const channelResponseModel = jsonApiFormatter.deserialize(channelResponse.data) as IChannelResponseModel;

			data.value = data.value ?? {};
			data.value[channelResponseModel.id] = await storeRecordFactory(storesManager, {
				...channelResponseModel,
				...{ deviceId: channelResponseModel.device.id },
			});

			await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(data.value[channelResponseModel.id]), DB_TABLE_CHANNELS);

			meta.value[channelResponseModel.id] = channelResponseModel.type;
		} catch (e: any) {
			if (e instanceof AxiosError && e.status === 404) {
				await unset({
					id: payload.id,
				});

				return true;
			}

			throw new ApiError('devices-module.channels.get.failed', e, 'Fetching channel failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const fetch = async (payload?: IChannelsFetchActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.items.includes(payload?.deviceId ?? 'all')) {
			return false;
		}

		const fromDatabase = await loadAllRecords({ deviceId: payload?.deviceId });

		if (fromDatabase && payload?.refresh === false) {
			return true;
		}

		if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
			semaphore.value.fetching.items.push(payload?.deviceId ?? 'all');
		}

		firstLoad.value = firstLoad.value.filter((item) => item !== (payload?.deviceId ?? 'all'));
		firstLoad.value = [...new Set(firstLoad.value)];

		try {
			let channelsResponse: AxiosResponse<IChannelsResponseJson>;

			if (payload?.deviceId) {
				channelsResponse = await axios.get<IChannelsResponseJson>(`/${ModulePrefix.DEVICES}/v1/devices/${payload.deviceId}/channels`);
			} else {
				channelsResponse = await axios.get<IChannelsResponseJson>(`/${ModulePrefix.DEVICES}/v1/channels`);
			}

			const channelsResponseModel = jsonApiFormatter.deserialize(channelsResponse.data) as IChannelResponseModel[];

			const deviceIds: string[] = [];

			for (const channel of channelsResponseModel) {
				data.value = data.value ?? {};
				data.value[channel.id] = await storeRecordFactory(storesManager, {
					...channel,
					...{ deviceId: channel.device.id },
				});

				deviceIds.push(channel.device.id);

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(data.value[channel.id]), DB_TABLE_CHANNELS);

				meta.value[channel.id] = channel.type;
			}

			if (payload && payload.deviceId) {
				firstLoad.value.push(payload.deviceId);
				firstLoad.value = [...new Set(firstLoad.value)];

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);
				const indexedDbIds: string[] = allRecords.filter((record) => record.device.id === payload.deviceId).map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CHANNELS);

					delete meta.value[id];
				}
			} else {
				firstLoad.value.push('all');
				firstLoad.value = [...new Set(firstLoad.value)];

				const uniqueDeviceIds = [...new Set(deviceIds)];

				for (const deviceId of uniqueDeviceIds) {
					firstLoad.value.push(deviceId);
					firstLoad.value = [...new Set(firstLoad.value)];
				}

				// Get all current IDs from IndexedDB
				const allRecords = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);
				const indexedDbIds: string[] = allRecords.map((record) => record.id);

				// Get the IDs from the latest changes
				const serverIds: string[] = Object.keys(data.value ?? {});

				// Find IDs that are in IndexedDB but not in the server response
				const idsToRemove: string[] = indexedDbIds.filter((id) => !serverIds.includes(id));

				// Remove records that are no longer present on the server
				for (const id of idsToRemove) {
					await removeRecord(id, DB_TABLE_CHANNELS);

					delete meta.value[id];
				}
			}
		} catch (e: any) {
			if (e instanceof AxiosError && e.status === 404 && typeof payload?.deviceId !== 'undefined') {
				try {
					const devicesStore = storesManager.getStore(devicesStoreKey);

					await devicesStore.get({
						id: payload?.deviceId,
					});
				} catch (e: any) {
					if (e instanceof ApiError && e.exception instanceof AxiosError && e.exception.status === 404) {
						const devicesStore = storesManager.getStore(devicesStoreKey);

						devicesStore.unset({
							id: payload?.deviceId,
						});

						return true;
					}
				}
			}

			throw new ApiError('devices-module.channels.fetch.failed', e, 'Fetching channels failed.');
		} finally {
			if (payload?.refresh === undefined || payload?.refresh === true || !fromDatabase) {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== (payload?.deviceId ?? 'all'));
			}
		}

		return true;
	};

	const add = async (payload: IChannelsAddActionPayload): Promise<IChannel> => {
		const newChannel = await storeRecordFactory(storesManager, {
			...payload.data,
			...{
				id: payload?.id,
				type: payload?.type,
				category: ChannelCategory.GENERIC,
				draft: payload?.draft,
				deviceId: payload.device.id,
			},
		});

		semaphore.value.creating.push(newChannel.id);

		data.value = data.value ?? {};
		data.value[newChannel.id] = newChannel;

		if (newChannel.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newChannel.id);

			return newChannel;
		} else {
			try {
				const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

				const properties = channelPropertiesStore.findForChannel(newChannel.id);

				const channelControlsStore = storesManager.getStore(channelControlsStoreKey);

				const controls = channelControlsStore.findForChannel(newChannel.id);

				const createdChannel = await axios.post<IChannelResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${payload.device.id}/channels`,
					jsonApiFormatter.serialize({
						stuff: {
							...newChannel,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const createdChannelModel = jsonApiFormatter.deserialize(createdChannel.data) as IChannelResponseModel;

				data.value[createdChannelModel.id] = await storeRecordFactory(storesManager, {
					...createdChannelModel,
					...{ deviceId: createdChannelModel.device.id },
				});

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(data.value[createdChannelModel.id]), DB_TABLE_CHANNELS);

				meta.value[createdChannelModel.id] = createdChannelModel.type;
			} catch (e: any) {
				// Transformer could not be created on api, we have to remove it from database
				delete data.value[newChannel.id];

				throw new ApiError('devices-module.channels.create.failed', e, 'Create new channel failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newChannel.id);
			}

			return data.value[newChannel.id];
		}
	};

	const edit = async (payload: IChannelsEditActionPayload): Promise<IChannel> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.channels.update.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.channels.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IChannel;

		data.value[payload.id] = await storeRecordFactory(storesManager, {
			...updatedRecord,
		});

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

				const properties = channelPropertiesStore.findForChannel(updatedRecord.id);

				const channelControlsStore = storesManager.getStore(channelControlsStoreKey);

				const controls = channelControlsStore.findForChannel(updatedRecord.id);

				const updatedChannel = await axios.patch<IChannelResponseJson>(
					`/${ModulePrefix.DEVICES}/v1/devices/${updatedRecord.device.id}/channels/${updatedRecord.id}`,
					jsonApiFormatter.serialize({
						stuff: {
							...updatedRecord,
							properties,
							controls,
						},
						includeNames: ['properties', 'controls'],
					})
				);

				const updatedChannelModel = jsonApiFormatter.deserialize(updatedChannel.data) as IChannelResponseModel;

				data.value[updatedChannelModel.id] = await storeRecordFactory(storesManager, {
					...updatedChannelModel,
					...{ deviceId: updatedChannelModel.device.id },
				});

				await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(data.value[updatedChannelModel.id]), DB_TABLE_CHANNELS);

				meta.value[updatedChannelModel.id] = updatedChannelModel.type;
			} catch (e: any) {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(updatedRecord.device.id);

				if (device !== null) {
					// Updating entity on api failed, we need to refresh entity
					await get({ deviceId: device.id, id: payload.id });
				}

				throw new ApiError('devices-module.channels.update.failed', e, 'Edit channel failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}

			return data.value[payload.id];
		}
	};

	const save = async (payload: IChannelsSaveActionPayload): Promise<IChannel> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('devices-module.channels.save.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.channels.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

			const properties = channelPropertiesStore.findForChannel(recordToSave.id);

			const channelControlsStore = storesManager.getStore(channelControlsStoreKey);

			const controls = channelControlsStore.findForChannel(recordToSave.id);

			const savedChannel = await axios.post<IChannelResponseJson>(
				`/${ModulePrefix.DEVICES}/v1/devices/${recordToSave.device.id}/channels`,
				jsonApiFormatter.serialize({
					stuff: {
						...recordToSave,
						properties,
						controls,
					},
					includeNames: ['properties', 'controls'],
				})
			);

			const savedChannelModel = jsonApiFormatter.deserialize(savedChannel.data) as IChannelResponseModel;

			data.value[savedChannelModel.id] = await storeRecordFactory(storesManager, {
				...savedChannelModel,
				...{ deviceId: savedChannelModel.device.id },
			});

			await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(data.value[savedChannelModel.id]), DB_TABLE_CHANNELS);

			meta.value[savedChannelModel.id] = savedChannelModel.type;
		} catch (e: any) {
			throw new ApiError('devices-module.channels.save.failed', e, 'Save draft channel failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}

		return data.value[payload.id];
	};

	const remove = async (payload: IChannelsRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('devices-module.channels.delete.inProgress');
		}

		if (!data.value || !Object.keys(data.value).includes(payload.id)) {
			throw new Error('devices-module.channels.delete.failed');
		}

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		await removeRecord(payload.id, DB_TABLE_CHANNELS);

		delete meta.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
		} else {
			try {
				await axios.delete(`/${ModulePrefix.DEVICES}/v1/devices/${recordToDelete.device.id}/channels/${recordToDelete.id}`);
			} catch (e: any) {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(recordToDelete.device.id);

				if (device !== null) {
					// Deleting entity on api failed, we need to refresh entity
					await get({ deviceId: device.id, id: payload.id });
				}

				throw new ApiError('devices-module.channels.delete.failed', e, 'Delete channel failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const socketData = async (payload: IChannelsSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.CHANNEL_DOCUMENT_REPORTED,
				RoutingKeys.CHANNEL_DOCUMENT_CREATED,
				RoutingKeys.CHANNEL_DOCUMENT_UPDATED,
				RoutingKeys.CHANNEL_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: ChannelDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<ChannelDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (payload.routingKey === RoutingKeys.CHANNEL_DOCUMENT_DELETED) {
			await removeRecord(body.id, DB_TABLE_CHANNELS);

			delete meta.value[body.id];

			if (data.value && body.id in data.value) {
				const recordToDelete = data.value[body.id];

				delete data.value[body.id];

				const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);
				const channelControlsStore = storesManager.getStore(channelControlsStoreKey);

				channelPropertiesStore.unset({ channel: recordToDelete });
				channelControlsStore.unset({ channel: recordToDelete });
			}
		} else {
			if (payload.routingKey === RoutingKeys.CHANNEL_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			if (data.value && body.id in data.value) {
				const record = await storeRecordFactory(storesManager, {
					...data.value[body.id],
					...{
						category: body.category,
						name: body.name,
						comment: body.comment,
						deviceId: body.device,
					},
				});

				if (!isEqual(JSON.parse(JSON.stringify(data.value[body.id])), JSON.parse(JSON.stringify(record)))) {
					data.value[body.id] = record;

					await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

					meta.value[record.id] = record.type;
				}
			} else {
				const devicesStore = storesManager.getStore(devicesStoreKey);

				const device = devicesStore.findById(body.device);

				if (device !== null) {
					try {
						await get({
							deviceId: device.id,
							id: body.id,
						});
					} catch {
						return false;
					}
				} else {
					try {
						await devicesStore.get({ id: body.device });

						const device = devicesStore.findById(body.device);

						if (device === null) {
							return false;
						}

						await fetch({ deviceId: device.id });
					} catch {
						return false;
					}
				}
			}
		}

		return true;
	};

	const insertData = async (payload: IChannelsInsertDataActionPayload): Promise<boolean> => {
		data.value = data.value ?? {};

		let documents: ChannelDocument[];

		if (Array.isArray(payload.data)) {
			documents = payload.data;
		} else {
			documents = [payload.data];
		}

		const deviceIds = [];

		for (const doc of documents) {
			const isValid = jsonSchemaValidator.compile<ChannelDocument>(exchangeDocumentSchema);

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
						entity: 'channel',
					},
					category: doc.category,
					identifier: doc.identifier,
					name: doc.name,
					comment: doc.comment,
					deviceId: doc.device,
				},
			});

			if (documents.length === 1) {
				data.value[doc.id] = record;
			}

			await addRecord<IChannelDatabaseRecord>(databaseRecordFactory(record), DB_TABLE_CHANNELS);

			meta.value[record.id] = record.type;

			deviceIds.push(doc.device);
		}

		if (documents.length > 1) {
			const uniqueDeviceIds = [...new Set(deviceIds)];

			if (uniqueDeviceIds.length > 1) {
				firstLoad.value.push('all');
				firstLoad.value = [...new Set(firstLoad.value)];
			}

			for (const deviceId of uniqueDeviceIds) {
				firstLoad.value.push(deviceId);
				firstLoad.value = [...new Set(firstLoad.value)];
			}
		}

		return true;
	};

	const loadRecord = async (payload: IChannelsLoadRecordActionPayload): Promise<boolean> => {
		const record = await getRecord<IChannelDatabaseRecord>(payload.id, DB_TABLE_CHANNELS);

		if (record) {
			data.value = data.value ?? {};
			data.value[payload.id] = await storeRecordFactory(storesManager, record);

			return true;
		}

		return false;
	};

	const loadAllRecords = async (payload?: IChannelsLoadAllRecordsActionPayload): Promise<boolean> => {
		const records = await getAllRecords<IChannelDatabaseRecord>(DB_TABLE_CHANNELS);

		data.value = data.value ?? {};

		for (const record of records) {
			if (payload?.deviceId && payload?.deviceId !== record?.device.id) {
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
		findForDevice,
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

export const registerChannelsStore = (pinia: Pinia): Store<string, IChannelsState, object, IChannelsActions> => {
	return useChannels(pinia);
};
