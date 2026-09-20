import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

import {
	DevicePropertyDocument,
	IDevice,
	IDeviceResponseData,
	IDeviceResponseModel,
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

export interface IDevicePropertyMeta extends IPropertyMeta {
	parent: 'device';
	type: 'dynamic' | 'variable' | 'mapped';
}

// STORE
// =====

export interface IDevicePropertiesState {
	semaphore: Ref<IDevicePropertiesStateSemaphore>;
	firstLoad: Ref<IDevice['id'][]>;
	data: Ref<{ [key: IDeviceProperty['id']]: IDeviceProperty } | undefined>;
	meta: Ref<{ [key: IDeviceProperty['id']]: IDevicePropertyMeta }>;
}

export interface IDevicePropertiesActions {
	// Getters
	firstLoadFinished: (deviceId: IDevice['id']) => boolean;
	getting: (id: IDeviceProperty['id']) => boolean;
	fetching: (deviceId: IDevice['id'] | null) => boolean;
	findById: (id: IDeviceProperty['id']) => IDeviceProperty | null;
	findByIdentifier: (device: IDevice, identifier: IDeviceProperty['identifier']) => IDeviceProperty | null;
	findForDevice: (deviceId: IDevice['id']) => IDeviceProperty[];
	findMeta: (id: IDeviceProperty['id']) => IDevicePropertyMeta | null;
	// Actions
	set: (payload: IDevicePropertiesSetActionPayload) => Promise<IDeviceProperty>;
	unset: (payload: IDevicePropertiesUnsetActionPayload) => Promise<void>;
	get: (payload: IDevicePropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IDevicePropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicePropertiesAddActionPayload) => Promise<IDeviceProperty>;
	edit: (payload: IDevicePropertiesEditActionPayload) => Promise<IDeviceProperty>;
	setState: (payload: IDevicePropertiesSetStateActionPayload) => Promise<IDeviceProperty>;
	save: (payload: IDevicePropertiesSaveActionPayload) => Promise<IDeviceProperty>;
	remove: (payload: IDevicePropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicePropertiesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDevicePropertiesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDevicePropertiesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IDevicePropertiesLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type DevicePropertiesStoreSetup = IDevicePropertiesState & IDevicePropertiesActions;

// STORE STATE
// ===========

export interface IDevicePropertiesStateSemaphore {
	fetching: IDevicePropertiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IDevicePropertiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IDeviceProperty extends IProperty {
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IDevicePropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	type: IDevicePropertyMeta;

	// Relations
	deviceId?: string;
	device?: IPlainRelation;
	parentId?: string | null;
	parent?: IPlainRelation | null;
}

// STORE ACTIONS
// =============

export interface IDevicePropertiesSetActionPayload {
	data: IDevicePropertyRecordFactoryPayload;
}

export interface IDevicePropertiesUnsetActionPayload {
	device?: IDevice;
	parent?: IDeviceProperty | null;
	id?: IDeviceProperty['id'];
}

export interface IDevicePropertiesGetActionPayload {
	device: IDevice;
	id: IDeviceProperty['id'];
	refresh?: boolean;
}

export interface IDevicePropertiesFetchActionPayload {
	device: IDevice;
	refresh?: boolean;
}

export interface IDevicePropertiesAddActionPayload extends IPropertiesAddActionPayload {
	type: IDevicePropertyMeta;
	device: IDevice;
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesEditActionPayload extends IPropertiesEditActionPayload {
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesSetStateActionPayload extends IPropertiesSetStateActionPayload {
	parent?: IDeviceProperty | null;
}

export interface IDevicePropertiesSaveActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesRemoveActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDevicePropertiesInsertDataActionPayload {
	data: DevicePropertyDocument | DevicePropertyDocument[];
}

export interface IDevicePropertiesLoadRecordActionPayload {
	id: IDeviceProperty['id'];
}

export interface IDevicePropertiesLoadAllRecordsActionPayload {
	device: IDevice;
}

// API RESPONSES JSONS
// ===================

export interface IDevicePropertyResponseJson extends TJsonApiBody {
	data: IDevicePropertyResponseData;
	includes?: (IDeviceResponseData | IDevicePropertyResponseData)[];
}

export interface IDevicePropertiesResponseJson extends TJsonApiBody {
	data: IDevicePropertyResponseData[];
	includes?: (IDeviceResponseData | IDevicePropertyResponseData)[];
}

export interface IDevicePropertyResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDevicePropertyResponseDataAttributes;
	relationships: IDevicePropertyResponseDataRelationships;
}

interface IDevicePropertyResponseDataAttributes {
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	actual_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending: boolean | Date;
	isValid: boolean;
}

interface IDevicePropertyResponseDataRelationships extends TJsonApiRelationships {
	device: TJsonApiRelation;
	parent: TJsonApiRelation;
	children: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDevicePropertyResponseModel extends IPropertyResponseModel {
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation | IDeviceResponseModel;
	parent?: IPlainRelation | IDevicePropertyResponseModel | null;
	children?: (IPlainRelation | IDevicePropertyResponseModel)[];
}

// DATABASE
// ========

export interface IDevicePropertyDatabaseRecord extends IPropertyDatabaseRecord {
	type: IDevicePropertyMeta;

	// Relations
	device: IPlainRelation;
	parent: IPlainRelation | null;
	children: IPlainRelation[];
}
