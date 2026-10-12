import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import {
	DeviceCategory,
	DeviceDocument,
	IChannelResponseData,
	IChannelResponseModel,
	IConnector,
	IConnectorResponseModel,
	IDeviceControlResponseData,
	IDeviceControlResponseModel,
	IDeviceProperty,
	IDevicePropertyResponseData,
	IDevicePropertyResponseModel,
	IEntityMeta,
	IPlainRelation,
} from '../../types';

export interface IDeviceMeta extends IEntityMeta {
	entity: 'device';
}

// STORE
// =====

export interface IDevicesState {
	semaphore: Ref<IDevicesStateSemaphore>;
	firstLoad: Ref<IConnector['id'][]>;
	data: Ref<{ [key: IDevice['id']]: IDevice } | undefined>;
	meta: Ref<{ [key: IDevice['id']]: IDeviceMeta }>;
}

export interface IDevicesActions {
	// Getters
	firstLoadFinished: (connectorId?: IConnector['id'] | null) => boolean;
	getting: (id: IDevice['id']) => boolean;
	fetching: (connectorId?: IConnector['id'] | null) => boolean;
	findById: (id: IDevice['id']) => IDevice | null;
	findForConnector: (connectorId: IConnector['id']) => IDevice[];
	findAll: () => IDevice[];
	findMeta: (id: IDevice['id']) => IDeviceMeta | null;
	// Actions
	set: (payload: IDevicesSetActionPayload) => Promise<IDevice>;
	unset: (payload: IDevicesUnsetActionPayload) => Promise<void>;
	get: (payload: IDevicesGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IDevicesFetchActionPayload) => Promise<boolean>;
	add: (payload: IDevicesAddActionPayload) => Promise<IDevice>;
	edit: (payload: IDevicesEditActionPayload) => Promise<IDevice>;
	save: (payload: IDevicesSaveActionPayload) => Promise<IDevice>;
	remove: (payload: IDevicesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDevicesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDevicesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDevicesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IDevicesLoadAllRecordsActionPayload) => Promise<boolean>;
}

export type DevicesStoreSetup = IDevicesState & IDevicesActions;

// STORE STATE
// ===========

export interface IDevicesStateSemaphore {
	fetching: IDevicesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

export interface IDevicesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

export interface IDevice {
	id: string;
	type: IDeviceMeta;

	draft: boolean;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	parents: IPlainRelation[];
	children: IPlainRelation[];

	channels: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	connector: IPlainRelation;

	owner: string | null;

	stateProperty: IDeviceProperty | null;
	hasComment: boolean;
	title: string;
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceRecordFactoryPayload {
	id?: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: string[];

	parents?: (IPlainRelation | IDeviceResponseModel)[];
	children?: (IPlainRelation | IDeviceResponseModel)[];

	channels?: (IPlainRelation | IChannelResponseModel)[];
	controls?: (IPlainRelation | IDeviceControlResponseModel)[];
	properties?: (IPlainRelation | IDevicePropertyResponseModel)[];

	connectorId?: string;
	connector?: IPlainRelation;

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IDevicesSetActionPayload {
	data: IDeviceRecordFactoryPayload;
}

export interface IDevicesUnsetActionPayload {
	connector?: IConnector;
	id?: IDevice['id'];
}

export interface IDevicesGetActionPayload {
	id: IDevice['id'];
	connectorId?: IConnector['id'];
	refresh?: boolean;
}

export interface IDevicesFetchActionPayload {
	connectorId?: IConnector['id'];
	refresh?: boolean;
}

export interface IDevicesAddActionPayload {
	id?: IDevice['id'];
	type: IDeviceMeta;

	draft?: IDevice['draft'];

	connector: IConnector;

	parents?: IDevice[];

	data: {
		identifier: IDevice['identifier'];
		name?: IDevice['name'];
		comment?: IDevice['comment'];
	};
}

export interface IDevicesEditActionPayload {
	id: IDevice['id'];

	data: {
		identifier?: IDevice['identifier'];
		name?: IDevice['name'];
		comment?: IDevice['comment'];
	};
}

export interface IDevicesSaveActionPayload {
	id: IDevice['id'];
}

export interface IDevicesRemoveActionPayload {
	id: IDevice['id'];
}

export interface IDevicesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDevicesInsertDataActionPayload {
	data: DeviceDocument | DeviceDocument[];
}

export interface IDevicesLoadRecordActionPayload {
	id: IDevice['id'];
}

export interface IDevicesLoadAllRecordsActionPayload {
	connectorId?: IConnector['id'];
}

// API RESPONSES JSONS
// ===================

export interface IDeviceResponseJson extends TJsonApiBody {
	data: IDeviceResponseData;
	included?: (IDevicePropertyResponseData | IDeviceControlResponseData | IChannelResponseData)[];
}

export interface IDevicesResponseJson extends TJsonApiBody {
	data: IDeviceResponseData[];
	included?: (IDevicePropertyResponseData | IDeviceControlResponseData | IChannelResponseData)[];
}

export interface IDeviceResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDeviceResponseDataAttributes;
	relationships: IDeviceResponseDataRelationships;
}

interface IDeviceResponseDataAttributes {
	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;
}

interface IDeviceResponseDataRelationships extends TJsonApiRelationships {
	properties: TJsonApiRelation;
	controls: TJsonApiRelation;
	channels: TJsonApiRelation;
	parents: TJsonApiRelation;
	children: TJsonApiRelation;
	connector: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDeviceResponseModel extends TJsonaModel {
	id: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;

	// Relations
	relationshipNames: string[];

	properties: (IPlainRelation | IDevicePropertyResponseModel)[];
	controls: (IPlainRelation | IDeviceControlResponseModel)[];
	channels: (IPlainRelation | IChannelResponseModel)[];
	parents: (IPlainRelation | IDeviceResponseModel)[];
	children: (IPlainRelation | IDeviceResponseModel)[];
	connector: IPlainRelation | IConnectorResponseModel;
}

// DATABASE
// ========

export interface IDeviceDatabaseRecord {
	id: string;
	type: IDeviceMeta;

	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: string[];

	parents: IPlainRelation[];
	children: IPlainRelation[];

	channels: IPlainRelation[];
	controls: IPlainRelation[];
	properties: IPlainRelation[];

	connector: IPlainRelation;

	owner: string | null;
}
