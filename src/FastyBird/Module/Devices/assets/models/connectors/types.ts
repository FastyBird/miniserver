import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import {
	ConnectorCategory,
	ConnectorDocument,
	IConnectorControlResponseData,
	IConnectorControlResponseModel,
	IConnectorProperty,
	IConnectorPropertyResponseData,
	IConnectorPropertyResponseModel,
	IDeviceResponseData,
	IDeviceResponseModel,
	IEntityMeta,
	IPlainRelation,
} from '../../types';

export interface IConnectorMeta extends IEntityMeta {
	entity: 'connector';
}

// STORE
// =====

export interface IConnectorsState {
	semaphore: Ref<IConnectorsStateSemaphore>;
	firstLoad: Ref<boolean>;
	data: Ref<{ [key: IConnector['id']]: IConnector } | undefined>;
	meta: Ref<{ [key: IConnector['id']]: IConnectorMeta }>;
}

export interface IConnectorsActions {
	// Getters
	firstLoadFinished: () => boolean;
	getting: (id: IConnector['id']) => boolean;
	fetching: () => boolean;
	findById: (id: IConnector['id']) => IConnector | null;
	findAll: () => IConnector[];
	findMeta: (id: IConnector['id']) => IConnectorMeta | null;
	// Actions
	set: (payload: IConnectorsSetActionPayload) => Promise<IConnector>;
	unset: (payload: IConnectorsUnsetActionPayload) => Promise<void>;
	get: (payload: IConnectorsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IConnectorsFetchActionPayload) => Promise<boolean>;
	add: (payload: IConnectorsAddActionPayload) => Promise<IConnector>;
	edit: (payload: IConnectorsEditActionPayload) => Promise<IConnector>;
	save: (payload: IConnectorsSaveActionPayload) => Promise<IConnector>;
	remove: (payload: IConnectorsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IConnectorsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IConnectorsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IConnectorsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: () => Promise<boolean>;
}

export type ConnectorsStoreSetup = IConnectorsState & IConnectorsActions;

// STORE STATE
// ===========

export interface IConnectorsStateSemaphore {
	fetching: IConnectorsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IConnectorsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IConnector {
	id: string;
	type: IConnectorMeta;

	draft: boolean;

	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	enabled: boolean;

	// Relations
	relationshipNames: string[];

	devices: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	owner: string | null;

	isEnabled: boolean;
	stateProperty: IConnectorProperty | null;
	hasComment: boolean;
	title: string;
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorRecordFactoryPayload {
	id?: string;
	type: IConnectorMeta;

	category: ConnectorCategory;
	identifier: string;
	name?: string | null;
	comment?: string | null;
	enabled?: boolean;

	// Relations
	relationshipNames?: string[];

	devices?: (IPlainRelation | IDeviceResponseModel)[];
	controls?: (IPlainRelation | IConnectorControlResponseModel)[];
	properties?: (IPlainRelation | IConnectorPropertyResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IConnectorsSetActionPayload {
	data: IConnectorRecordFactoryPayload;
}

export interface IConnectorsUnsetActionPayload {
	id?: IConnector['id'];
}

export interface IConnectorsGetActionPayload {
	id: IConnector['id'];
	refresh?: boolean;
}

export interface IConnectorsFetchActionPayload {
	refresh?: boolean;
}

export interface IConnectorsAddActionPayload {
	id?: IConnector['id'];
	type: IConnectorMeta;

	draft?: IConnector['draft'];

	data: {
		identifier: IConnector['identifier'];
		name?: IConnector['name'];
		comment?: IConnector['comment'];
		enabled?: IConnector['enabled'];
	};
}

export interface IConnectorsEditActionPayload {
	id: IConnector['id'];

	data: {
		name?: IConnector['name'];
		comment?: IConnector['comment'];
		enabled?: IConnector['enabled'];
	};
}

export interface IConnectorsSaveActionPayload {
	id: IConnector['id'];
}

export interface IConnectorsRemoveActionPayload {
	id: IConnector['id'];
}

export interface IConnectorsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IConnectorsInsertDataActionPayload {
	data: ConnectorDocument | ConnectorDocument[];
}

export interface IConnectorsLoadRecordActionPayload {
	id: IConnector['id'];
}

// API RESPONSES JSONS
// ===================

export interface IConnectorResponseJson extends TJsonApiBody {
	data: IConnectorResponseData;
	included?: (IConnectorPropertyResponseData | IConnectorControlResponseData | IDeviceResponseData)[];
}

export interface IConnectorsResponseJson extends TJsonApiBody {
	data: IConnectorResponseData[];
	included?: (IConnectorPropertyResponseData | IConnectorControlResponseData | IDeviceResponseData)[];
}

export interface IConnectorResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IConnectorResponseDataAttributes;
	relationships: IConnectorResponseDataRelationships;
}

interface IConnectorResponseDataAttributes {
	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	enabled: boolean;

	owner: string | null;
}

interface IConnectorResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	devices: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IConnectorResponseModel extends TJsonaModel {
	id: string;
	type: IConnectorMeta;

	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	enabled: boolean;

	owner: string | null;

	// Relations
	properties: (IPlainRelation | IConnectorPropertyResponseModel)[];
	controls: (IPlainRelation | IConnectorControlResponseModel)[];
	devices: (IPlainRelation | IDeviceResponseModel)[];
}

// DATABASE
// ========

export interface IConnectorDatabaseRecord {
	id: string;
	type: IConnectorMeta;

	category: ConnectorCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	enabled: boolean;

	// Relations
	relationshipNames: string[];

	devices: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	owner: string | null;
}
