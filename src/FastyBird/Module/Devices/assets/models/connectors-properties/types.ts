import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships } from 'jsona/lib/JsonaTypes';

import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

import {
	ConnectorPropertyDocument,
	IConnector,
	IConnectorResponseData,
	IConnectorResponseModel,
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

export interface IConnectorPropertyMeta extends IPropertyMeta {
	parent: 'connector';
	type: 'dynamic' | 'variable';
}

// STORE
// =====

export interface IConnectorPropertiesState {
	semaphore: Ref<IConnectorPropertiesStateSemaphore>;
	firstLoad: Ref<IConnector['id'][]>;
	data: Ref<{ [key: IConnectorProperty['id']]: IConnectorProperty } | undefined>;
	meta: Ref<{ [key: IConnectorProperty['id']]: IConnectorPropertyMeta }>;
}

export interface IConnectorPropertiesActions {
	// Getters
	firstLoadFinished: (connectorId: IConnector['id']) => boolean;
	getting: (id: IConnectorProperty['id']) => boolean;
	fetching: (connectorId: IConnector['id'] | null) => boolean;
	findById: (id: IConnectorProperty['id']) => IConnectorProperty | null;
	findByIdentifier: (connector: IConnector, identifier: IConnectorProperty['identifier']) => IConnectorProperty | null;
	findForConnector: (connectorId: IConnector['id']) => IConnectorProperty[];
	findMeta: (id: IConnectorProperty['id']) => IConnectorPropertyMeta | null;
	// Actions
	set: (payload: IConnectorPropertiesSetActionPayload) => Promise<IConnectorProperty>;
	unset: (payload: IConnectorPropertiesUnsetActionPayload) => Promise<void>;
	get: (payload: IConnectorPropertiesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IConnectorPropertiesFetchActionPayload) => Promise<boolean>;
	add: (payload: IConnectorPropertiesAddActionPayload) => Promise<IConnectorProperty>;
	edit: (payload: IConnectorPropertiesEditActionPayload) => Promise<IConnectorProperty>;
	setState: (payload: IConnectorPropertiesSetStateActionPayload) => Promise<IConnectorProperty>;
	save: (payload: IConnectorPropertiesSaveActionPayload) => Promise<IConnectorProperty>;
	remove: (payload: IConnectorPropertiesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IConnectorPropertiesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IConnectorPropertiesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IConnectorPropertiesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IConnectorPropertiesLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type ConnectorPropertiesStoreSetup = IConnectorPropertiesState & IConnectorPropertiesActions;

// STORE STATE
// ===========

export interface IConnectorPropertiesStateSemaphore {
	fetching: IConnectorPropertiesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IConnectorPropertiesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IConnectorProperty extends IProperty {
	type: IConnectorPropertyMeta;

	// Relations
	connector: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorPropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
	type: IConnectorPropertyMeta;

	// Relations
	connectorId?: string;
	connector?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IConnectorPropertiesSetActionPayload {
	data: IConnectorPropertyRecordFactoryPayload;
}

export interface IConnectorPropertiesUnsetActionPayload {
	connector?: IConnector;
	id?: IConnectorProperty['id'];
}

export interface IConnectorPropertiesGetActionPayload {
	connector: IConnector;
	id: IConnectorProperty['id'];
	refresh?: boolean;
}

export interface IConnectorPropertiesFetchActionPayload {
	connector: IConnector;
	refresh?: boolean;
}

export interface IConnectorPropertiesAddActionPayload extends IPropertiesAddActionPayload {
	type: IConnectorPropertyMeta;
	connector: IConnector;
}

export type IConnectorPropertiesEditActionPayload = IPropertiesEditActionPayload;

export type IConnectorPropertiesSetStateActionPayload = IPropertiesSetStateActionPayload;

export interface IConnectorPropertiesSaveActionPayload {
	id: IConnectorProperty['id'];
}

export interface IConnectorPropertiesRemoveActionPayload {
	id: IConnectorProperty['id'];
}

export interface IConnectorPropertiesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IConnectorPropertiesInsertDataActionPayload {
	data: ConnectorPropertyDocument | ConnectorPropertyDocument[];
}

export interface IConnectorPropertiesLoadRecordActionPayload {
	id: IConnectorProperty['id'];
}

export interface IConnectorPropertiesLoadAllRecordsActionPayload {
	connector: IConnector;
}

// API RESPONSES JSONS
// ===================

export interface IConnectorPropertyResponseJson extends TJsonApiBody {
	data: IConnectorPropertyResponseData;
	includes?: IConnectorResponseData[];
}

export interface IConnectorPropertiesResponseJson extends TJsonApiBody {
	data: IConnectorPropertyResponseData[];
	includes?: IConnectorResponseData[];
}

export interface IConnectorPropertyResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IConnectorPropertyResponseDataAttributes;
	relationships: IConnectorPropertyResponseDataRelationships;
}

interface IConnectorPropertyResponseDataAttributes {
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

interface IConnectorPropertyResponseDataRelationships extends TJsonApiRelationships {
	connector: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IConnectorPropertyResponseModel extends IPropertyResponseModel {
	type: IConnectorPropertyMeta;

	// Relations
	connector: IPlainRelation | IConnectorResponseModel;
}

// DATABASE
// ========

export interface IConnectorPropertyDatabaseRecord extends IPropertyDatabaseRecord {
	type: IConnectorPropertyMeta;

	// Relations
	connector: IPlainRelation;
}
