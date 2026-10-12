import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from '@fastybird/miniserver-core';

import {
	DeviceControlDocument,
	IControl,
	IControlDatabaseRecord,
	IControlMeta,
	IControlRecordFactoryPayload,
	IControlResponseModel,
	IControlsAddActionPayload,
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
	IPlainRelation,
} from '../../types';

export interface IDeviceControlMeta extends IControlMeta {
	parent: 'device';
}

// STORE
// =====

export interface IDeviceControlsState {
	semaphore: Ref<IDeviceControlsStateSemaphore>;
	firstLoad: Ref<IDevice['id'][]>;
	data: Ref<{ [key: IDeviceControl['id']]: IDeviceControl } | undefined>;
	meta: Ref<{ [key: IDeviceControl['id']]: IDeviceControlMeta }>;
}

export interface IDeviceControlsActions {
	// Getters
	firstLoadFinished: (deviceId: IDevice['id']) => boolean;
	getting: (id: IDeviceControl['id']) => boolean;
	fetching: (deviceId: IDevice['id'] | null) => boolean;
	findById: (id: IDeviceControl['id']) => IDeviceControl | null;
	findByName: (device: IDevice, name: IDeviceControl['name']) => IDeviceControl | null;
	findForDevice: (deviceId: IDevice['id']) => IDeviceControl[];
	findMeta: (id: IDeviceControl['id']) => IDeviceControlMeta | null;
	// Actions
	set: (payload: IDeviceControlsSetActionPayload) => Promise<IDeviceControl>;
	unset: (payload: IDeviceControlsUnsetActionPayload) => Promise<void>;
	get: (payload: IDeviceControlsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDeviceControlsFetchActionPayload) => Promise<boolean>;
	add: (payload: IDeviceControlsAddActionPayload) => Promise<IDeviceControl>;
	save: (payload: IDeviceControlsSaveActionPayload) => Promise<IDeviceControl>;
	remove: (payload: IDeviceControlsRemoveActionPayload) => Promise<boolean>;
	transmitCommand: (payload: IDeviceControlsTransmitCommandActionPayload) => Promise<boolean>;
	socketData: (payload: IDeviceControlsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDeviceControlsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDeviceControlsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IDeviceControlsLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type DeviceControlsStoreSetup = IDeviceControlsState & IDeviceControlsActions;
// STORE STATE
// ===========

export interface IDeviceControlsStateSemaphore {
	fetching: IDeviceControlsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IDeviceControlsStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IDeviceControl extends IControl {
	type: IDeviceControlMeta;

	// Relations
	device: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceControlRecordFactoryPayload extends IControlRecordFactoryPayload {
	type: IDeviceControlMeta;

	// Relations
	deviceId?: string;
	device?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IDeviceControlsSetActionPayload {
	data: IDeviceControlRecordFactoryPayload;
}

export interface IDeviceControlsUnsetActionPayload {
	device?: IDevice;
	id?: IDeviceControl['id'];
}

export interface IDeviceControlsGetActionPayload {
	device: IDevice;
	id: IDeviceControl['id'];
	refresh?: boolean;
}

export interface IDeviceControlsFetchActionPayload {
	device: IDevice;
	refresh?: boolean;
}

export interface IDeviceControlsAddActionPayload extends IControlsAddActionPayload {
	type: IDeviceControlMeta;
	device: IDevice;
}

export interface IDeviceControlsSaveActionPayload {
	id: IDeviceControl['id'];
}

export interface IDeviceControlsRemoveActionPayload {
	id: IDeviceControl['id'];
}

export interface IDeviceControlsTransmitCommandActionPayload {
	id: IDeviceControl['id'];
	value?: string;
}

export interface IDeviceControlsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDeviceControlsInsertDataActionPayload {
	data: DeviceControlDocument | DeviceControlDocument[];
}

export interface IDeviceControlsLoadRecordActionPayload {
	id: IDeviceControl['id'];
}

export interface IDeviceControlsLoadAllRecordsActionPayload {
	device: IDevice;
}

// API RESPONSES JSONS
// ===================

export interface IDeviceControlResponseJson extends TJsonApiBody {
	data: IDeviceControlResponseData;
	includes?: IDeviceResponseData[];
}

export interface IDeviceControlsResponseJson extends TJsonApiBody {
	data: IDeviceControlResponseData[];
	includes?: IDeviceResponseData[];
}

export interface IDeviceControlResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDeviceControlResponseDataAttributes;
	relationships: IDeviceControlResponseDataRelationships;
}

interface IDeviceControlResponseDataAttributes {
	name: string;
}

interface IDeviceControlResponseDataRelationships extends TJsonApiRelationships {
	device: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDeviceControlResponseModel extends IControlResponseModel {
	type: IDeviceControlMeta;

	// Relations
	device: IPlainRelation | IDeviceResponseModel;
}

// DATABASE
// ========

export interface IDeviceControlDatabaseRecord extends IControlDatabaseRecord {
	type: IDeviceControlMeta;

	// Relations
	device: IPlainRelation;
}
