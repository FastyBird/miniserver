import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from '@fastybird/miniserver-core';

import {
	ChannelControlDocument,
	IChannel,
	IChannelResponseData,
	IChannelResponseModel,
	IControl,
	IControlDatabaseRecord,
	IControlMeta,
	IControlRecordFactoryPayload,
	IControlResponseModel,
	IControlsAddActionPayload,
	IPlainRelation,
} from '../../types';

export interface IChannelControlMeta extends IControlMeta {
	parent: 'channel';
}

// STORE
// =====

export interface IChannelControlsState {
	semaphore: Ref<IChannelControlsStateSemaphore>;
	firstLoad: Ref<IChannel['id'][]>;
	data: Ref<{ [key: IChannelControl['id']]: IChannelControl } | undefined>;
	meta: Ref<{ [key: IChannelControl['id']]: IChannelControlMeta }>;
}

export interface IChannelControlsActions {
	// Getters
	firstLoadFinished: (channelId: IChannel['id']) => boolean;
	getting: (id: IChannelControl['id']) => boolean;
	fetching: (channelId: IChannel['id'] | null) => boolean;
	findById: (id: IChannelControl['id']) => IChannelControl | null;
	findByName: (channel: IChannel, name: IChannelControl['name']) => IChannelControl | null;
	findForChannel: (channelId: IChannel['id']) => IChannelControl[];
	findMeta: (id: IChannelControl['id']) => IChannelControlMeta | null;
	// Actions
	set: (payload: IChannelControlsSetActionPayload) => Promise<IChannelControl>;
	unset: (payload: IChannelControlsUnsetActionPayload) => Promise<void>;
	get: (payload: IChannelControlsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IChannelControlsFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelControlsAddActionPayload) => Promise<IChannelControl>;
	save: (payload: IChannelControlsSaveActionPayload) => Promise<IChannelControl>;
	remove: (payload: IChannelControlsRemoveActionPayload) => Promise<boolean>;
	transmitCommand: (payload: IChannelControlsTransmitCommandActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelControlsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IChannelControlsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IChannelControlsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IChannelControlsLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type ChannelControlsStoreSetup = IChannelControlsState & IChannelControlsActions;

// STORE STATE
// ===========

export interface IChannelControlsStateSemaphore {
	fetching: IChannelControlsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IChannelControlsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IChannelControl extends IControl {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IChannelControlRecordFactoryPayload extends IControlRecordFactoryPayload {
	type: IChannelControlMeta;

	// Relations
	channelId?: string;
	channel?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IChannelControlsSetActionPayload {
	data: IChannelControlRecordFactoryPayload;
}

export interface IChannelControlsUnsetActionPayload {
	channel?: IChannel;
	id?: IChannelControl['id'];
}

export interface IChannelControlsGetActionPayload {
	channel: IChannel;
	id: IChannelControl['id'];
	refresh?: boolean;
}

export interface IChannelControlsFetchActionPayload {
	channel: IChannel;
	refresh?: boolean;
}

export interface IChannelControlsAddActionPayload extends IControlsAddActionPayload {
	type: IChannelControlMeta;
	channel: IChannel;
}

export interface IChannelControlsSaveActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsRemoveActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsTransmitCommandActionPayload {
	id: IChannelControl['id'];
	value?: string;
}

export interface IChannelControlsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IChannelControlsInsertDataActionPayload {
	data: ChannelControlDocument | ChannelControlDocument[];
}

export interface IChannelControlsLoadRecordActionPayload {
	id: IChannelControl['id'];
}

export interface IChannelControlsLoadAllRecordsActionPayload {
	channel: IChannel;
}

// API RESPONSES JSONS
// ===================

export interface IChannelControlResponseJson extends TJsonApiBody {
	data: IChannelControlResponseData;
	includes?: IChannelResponseData[];
}

export interface IChannelControlsResponseJson extends TJsonApiBody {
	data: IChannelControlResponseData[];
	includes?: IChannelResponseData[];
}

export interface IChannelControlResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelControlResponseDataAttributes;
	relationships: IChannelControlResponseDataRelationships;
}

interface IChannelControlResponseDataAttributes {
	name: string;
}

interface IChannelControlResponseDataRelationships extends TJsonApiRelationships {
	channel: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelControlResponseModel extends IControlResponseModel {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation | IChannelResponseModel;
}

// DATABASE
// ========

export interface IChannelControlDatabaseRecord extends IControlDatabaseRecord {
	type: IChannelControlMeta;

	// Relations
	channel: IPlainRelation;
}
