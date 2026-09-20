import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

import {
	ChannelPropertyDocument,
	IChannel,
	IChannelResponseData,
	IChannelResponseModel,
	IPlainRelation,
	IPropertiesAddActionPayload,
	IPropertiesEditActionPayload,
	IPropertiesSetStateActionPayload,
	IProperty,
	IPropertyDatabaseRecord,
	IPropertyMeta,
	IPropertyRecordFactoryPayload,
	IPropertyResponseModel,
	PropertyCategory,
} from '../../types';

export interface IChannelPropertyMeta extends IPropertyMeta {
	parent: 'channel';
	type: 'dynamic' | 'variable' | 'mapped';
}

// STORE
// =====

export interface IChannelPropertiesState {
	semaphore: Ref<IChannelPropertiesStateSemaphore>;
	firstLoad: Ref<IChannel['id'][]>;
	data: Ref<{ [key: IChannelProperty['id']]: IChannelProperty } | undefined>;
	meta: Ref<{ [key: IChannelProperty['id']]: IChannelPropertyMeta }>;
}

export interface IChannelPropertiesActions {
	// Getters
	firstLoadFinished: (channelId: IChannel['id']) => boolean;
	getting: (id: IChannelProperty['id']) => boolean;
	fetching: (channelId: IChannel['id'] | null) => boolean;
	findById: (id: IChannelProperty['id']) => IChannelProperty | null;
	findByIdentifier: (channel: IChannel, identifier: IChannelProperty['identifier']) => IChannelProperty | null;
	findForChannel: (channelId: IChannel['id']) => IChannelProperty[];
	findMeta: (id: IChannelProperty['id']) => IChannelPropertyMeta | null;
	// Actions
	set: (payload: IChannelPropertiesSetActionPayload) => Promise<IChannelProperty>;
	unset: (payload: IChannelPropertiesUnsetActionPayload) => Promise<void>;
	get: (payload: IChannelPropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IChannelPropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IChannelPropertiesAddActionPayload) => Promise<IChannelProperty>;
	edit: (payload: IChannelPropertiesEditActionPayload) => Promise<IChannelProperty>;
	save: (payload: IChannelPropertiesSaveActionPayload) => Promise<IChannelProperty>;
	setState: (payload: IChannelPropertiesSetStateActionPayload) => Promise<IChannelProperty>;
	remove: (payload: IChannelPropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IChannelPropertiesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IChannelPropertiesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IChannelPropertiesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IChannelPropertiesLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type ChannelPropertiesStoreSetup = IChannelPropertiesState & IChannelPropertiesActions;

// STORE STATE
// ===========

export interface IChannelPropertiesStateSemaphore {
	fetching: IChannelPropertiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IChannelPropertiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IChannelProperty extends IProperty {
	type: IChannelPropertyMeta;

	// Relations
	channel: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IChannelPropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	type: IChannelPropertyMeta;

	// Relations
	channelId?: string;
	channel?: IPlainRelation;
	parentId?: string | null;
	parent?: IPlainRelation | null;
}

// STORE ACTIONS
// =============

export interface IChannelPropertiesSetActionPayload {
	data: IChannelPropertyRecordFactoryPayload;
}

export interface IChannelPropertiesUnsetActionPayload {
	channel?: IChannel;
	parent?: IChannelProperty | null;
	id?: IChannelProperty['id'];
}

export interface IChannelPropertiesGetActionPayload {
	channel: IChannel;
	id: IChannelProperty['id'];
	refresh?: boolean;
}

export interface IChannelPropertiesFetchActionPayload {
	channel: IChannel;
	refresh?: boolean;
}

export interface IChannelPropertiesAddActionPayload extends IPropertiesAddActionPayload {
	type: IChannelPropertyMeta;
	channel: IChannel;
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesEditActionPayload extends IPropertiesEditActionPayload {
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesSetStateActionPayload extends IPropertiesSetStateActionPayload {
	parent?: IChannelProperty | null;
}

export interface IChannelPropertiesSaveActionPayload {
	id: IChannelProperty['id'];
}

export interface IChannelPropertiesRemoveActionPayload {
	id: IChannelProperty['id'];
}

export interface IChannelPropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IChannelPropertiesInsertDataActionPayload {
	data: ChannelPropertyDocument | ChannelPropertyDocument[];
}

export interface IChannelPropertiesLoadRecordActionPayload {
	id: IChannelProperty['id'];
}

export interface IChannelPropertiesLoadAllRecordsActionPayload {
	channel: IChannel;
}

// API RESPONSES JSONS
// ===================

export interface IChannelPropertyResponseJson extends TJsonApiBody {
	data: IChannelPropertyResponseData;
	includes?: (IChannelResponseData | IChannelPropertyResponseData)[];
}

export interface IChannelPropertiesResponseJson extends TJsonApiBody {
	data: IChannelPropertyResponseData[];
	includes?: (IChannelResponseData | IChannelPropertyResponseData)[];
}

export interface IChannelPropertyResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IChannelPropertyResponseDataAttributes;
	relationships: IChannelPropertyResponseDataRelationships;
}

interface IChannelPropertyResponseDataAttributes {
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType | null;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	actual_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending: boolean | Date;
	isValid: boolean;
}

interface IChannelPropertyResponseDataRelationships extends TJsonApiRelationships {
	channel: TJsonApiRelation;
	parent: TJsonApiRelation;
	children: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IChannelPropertyResponseModel extends IPropertyResponseModel {
	type: IChannelPropertyMeta;

	// Relations
	channel: IPlainRelation | IChannelResponseModel;
	parent?: IPlainRelation | IChannelPropertyResponseModel | null;
	children?: (IPlainRelation | IChannelPropertyResponseModel)[];
}

// DATABASE
// ========

export interface IChannelPropertyDatabaseRecord extends IPropertyDatabaseRecord {
	type: IChannelPropertyMeta;

	// Relations
	channel: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}
