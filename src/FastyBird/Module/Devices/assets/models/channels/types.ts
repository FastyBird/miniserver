import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import {
	ChannelCategory,
	ChannelDocument,
	IChannelControlResponseData,
	IChannelControlResponseModel,
	IChannelPropertyResponseData,
	IChannelPropertyResponseModel,
	IConnector,
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
	IEntityMeta,
	IPlainRelation,
} from '../../types';

export interface IChannelMeta extends IEntityMeta {
	entity: 'channel';
}

// STORE
// =====

export interface IChannelsState {
	semaphore: Ref<IChannelsStateSemaphore>;
	firstLoad: Ref<IDevice['id'][]>;
	data: Ref<{ [key: IChannel['id']]: IChannel } | undefined>;
	meta: Ref<{ [key: IChannel['id']]: IChannelMeta }>;
}

export interface IChannelsActions {
	// Getters
	firstLoadFinished: (deviceId?: IDevice['id'] | null) => boolean;
	getting: (id: IChannel['id']) => boolean;
	fetching: (deviceId?: IDevice['id'] | null) => boolean;
	findById: (id: IChannel['id']) => IChannel | null;
	findForDevice: (deviceId: string) => IChannel[];
	findAll: () => IChannel[];
	findMeta: (id: IChannel['id']) => IChannelMeta | null;
	// Actions
	set: (payload: IChannelsSetActionPayload) => Promise<IChannel>;
	unset: (payload: IChannelsUnsetActionPayload) => Promise<void>;
	get: (payload: IChannelsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IChannelsFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelsAddActionPayload) => Promise<IChannel>;
	edit: (payload: IChannelsEditActionPayload) => Promise<IChannel>;
	save: (payload: IChannelsSaveActionPayload) => Promise<IChannel>;
	remove: (payload: IChannelsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IChannelsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IChannelsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IChannelsLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type ChannelsStoreSetup = IChannelsState & IChannelsActions;

// STORE STATE
// ===========

export interface IChannelsStateSemaphore {
	fetching: IChannelsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IChannelsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

export interface IChannel {
	id: string;
	type: IChannelMeta;

	draft: boolean;

	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	controls: IPlainRelation[];
	properties: IPlainRelation[];

	device: IPlainRelation;

	hasComment: boolean;
	title: string;
}

// STORE DATA FACTORIES
// ====================

export interface IChannelRecordFactoryPayload {
	id?: string;
	type: IChannelMeta;

	category: ChannelCategory;
	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: string[];

	controls?: (IPlainRelation | IChannelControlResponseModel)[];
	properties?: (IPlainRelation | IChannelPropertyResponseModel)[];

	deviceId?: string;
	device?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IChannelsSetActionPayload {
	data: IChannelRecordFactoryPayload;
}

export interface IChannelsUnsetActionPayload {
	device?: IDevice;
	id?: IChannel['id'];
}

export interface IChannelsGetActionPayload {
	connectorId?: IConnector['id'];
	deviceId?: IDevice['id'];
	id: IChannel['id'];
	refresh?: boolean;
}

export interface IChannelsFetchActionPayload {
	connectorId?: IConnector['id'];
	deviceId?: IDevice['id'];
	refresh?: boolean;
}

export interface IChannelsAddActionPayload {
	id?: IChannel['id'];
	type: IChannelMeta;

	draft?: IChannel['draft'];

	device: IDevice;

	data: {
		identifier: IChannel['identifier'];
		name?: IChannel['name'];
		comment?: IChannel['comment'];
	};
}

export interface IChannelsEditActionPayload {
	id: IChannel['id'];

	data: {
		identifier?: IChannel['identifier'];
		name?: IChannel['name'];
		comment?: IChannel['comment'];
	};
}

export interface IChannelsSaveActionPayload {
	id: IChannel['id'];
}

export interface IChannelsRemoveActionPayload {
	id: IChannel['id'];
}

export interface IChannelsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IChannelsInsertDataActionPayload {
	data: ChannelDocument | ChannelDocument[];
}

export interface IChannelsLoadRecordActionPayload {
	id: IChannel['id'];
}

export interface IChannelsLoadAllRecordsActionPayload {
	connectorId?: IConnector['id'];
	deviceId?: IDevice['id'];
}

// API RESPONSES JSONS
// ===================

export interface IChannelResponseJson extends TJsonApiBody {
	data: IChannelResponseData;
	included?: (IChannelPropertyResponseData | IChannelControlResponseData | IDeviceResponseData)[];
}

export interface IChannelsResponseJson extends TJsonApiBody {
	data: IChannelResponseData[];
	included?: (IChannelPropertyResponseData | IChannelControlResponseData | IDeviceResponseData)[];
}

export interface IChannelResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelResponseDataAttributes;
	relationships: IChannelResponseDataRelationships;
}

interface IChannelResponseDataAttributes {
	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
}

interface IChannelResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	device: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelResponseModel extends TJsonaModel {
	id: string;
	type: IChannelMeta;

	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	properties: (IPlainRelation | IChannelPropertyResponseModel)[];
	controls: (IPlainRelation | IChannelControlResponseModel)[];
	device: IPlainRelation | IDeviceResponseModel;
}

// DATABASE
// ========

export interface IChannelDatabaseRecord {
	id: string;
	type: IChannelMeta;

	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	controls: IPlainRelation[];
	properties: IPlainRelation[];

	device: IPlainRelation;
}
