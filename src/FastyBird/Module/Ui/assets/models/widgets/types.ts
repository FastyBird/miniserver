import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import {
	IEntityMeta,
	IGroupResponseModel,
	IPlainRelation,
	ITabResponseData,
	ITabResponseModel,
	IWidgetDataSourceResponseData,
	IWidgetDataSourceResponseModel,
	IWidgetDisplayResponseData,
	IWidgetDisplayResponseModel,
} from '../../models/types';
import { WidgetDocument } from '../../types';

export interface IWidgetMeta extends IEntityMeta {
	type: string;
	entity: 'widget';
}

type RelationshipName = 'display' | 'dataSources' | 'tabs' | 'groups';

// STORE
// =====

export interface IWidgetsState {
	semaphore: IWidgetsStateSemaphore;
	firstLoad: boolean;
	data: { [key: IWidget['id']]: IWidget } | undefined;
	meta: { [key: IWidget['id']]: IWidgetMeta };
}

export interface IWidgetsGetters extends _GettersTree<IWidgetsState> {
	firstLoadFinished: (state: IWidgetsState) => () => boolean;
	getting: (state: IWidgetsState) => (id: IWidget['id']) => boolean;
	fetching: (state: IWidgetsState) => () => boolean;
	findById: (state: IWidgetsState) => (id: IWidget['id']) => IWidget | null;
	findAll: (state: IWidgetsState) => () => IWidget[];
	findMeta: (state: IWidgetsState) => (id: IWidget['id']) => IWidgetMeta | null;
}

export interface IWidgetsActions {
	set: (payload: IWidgetsSetActionPayload) => Promise<IWidget>;
	get: (payload: IWidgetsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IWidgetsFetchActionPayload) => Promise<boolean>;
	add: (payload: IWidgetsAddActionPayload) => Promise<IWidget>;
	edit: (payload: IWidgetsEditActionPayload) => Promise<IWidget>;
	save: (payload: IWidgetsSaveActionPayload) => Promise<IWidget>;
	remove: (payload: IWidgetsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IWidgetsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IWidgetsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IWidgetsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: () => Promise<boolean>;
}

// STORE STATE
// ===========

interface IWidgetsStateSemaphore {
	fetching: IWidgetsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IWidgetsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IWidget {
	id: string;
	type: IWidgetMeta;

	draft: boolean;

	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: RelationshipName[];

	display: IPlainRelation;
	dataSources: IPlainRelation[];
	tabs: IPlainRelation[];
	groups: IPlainRelation[];

	owner: string | null;

	// Transformer transformers
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IWidgetRecordFactoryPayload {
	id?: string;
	type: IWidgetMeta;

	identifier: string;
	name?: string | null;
	comment?: string | null;

	// Relations
	relationshipNames?: RelationshipName[];

	display?: IPlainRelation | IWidgetDisplayResponseModel;
	dataSources?: (IPlainRelation | IWidgetDataSourceResponseModel)[];
	tabs?: (IPlainRelation | ITabResponseModel)[];
	groups?: (IPlainRelation | IGroupResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IWidgetsSetActionPayload {
	data: IWidgetRecordFactoryPayload;
}

export interface IWidgetsGetActionPayload {
	id: IWidget['id'];
	refresh?: boolean;
}

export interface IWidgetsFetchActionPayload {
	refresh?: boolean;
}

export interface IWidgetsAddActionPayload {
	id?: IWidget['id'];
	type: IWidgetMeta;

	draft?: IWidget['draft'];

	data: {
		identifier: IWidget['identifier'];
		name?: IWidget['name'];
		comment?: IWidget['comment'];
	};
}

export interface IWidgetsEditActionPayload {
	id: IWidget['id'];

	data: {
		name?: IWidget['name'];
		comment?: IWidget['comment'];
	};
}

export interface IWidgetsSaveActionPayload {
	id: IWidget['id'];
}

export interface IWidgetsRemoveActionPayload {
	id: IWidget['id'];
}

export interface IWidgetsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IWidgetsInsertDataActionPayload {
	data: WidgetDocument | WidgetDocument[];
}

export interface IWidgetsLoadRecordActionPayload {
	id: IWidget['id'];
}

// API RESPONSES JSONS
// ===================

export interface IWidgetResponseJson extends TJsonApiBody {
	data: IWidgetResponseData;
	included?: (IWidgetDisplayResponseData | IWidgetDataSourceResponseData | ITabResponseData | IWidgetResponseData)[];
}

export interface IWidgetsResponseJson extends TJsonApiBody {
	data: IWidgetResponseData[];
	included?: (IWidgetDisplayResponseData | IWidgetDataSourceResponseData | ITabResponseData | IWidgetResponseData)[];
}

export interface IWidgetResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IWidgetResponseDataAttributes;
	relationships: IWidgetResponseDataRelationships;
}

interface IWidgetResponseDataAttributes {
	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;
}

interface IWidgetResponseDataRelationships extends TJsonApiRelationships {
	display: TJsonApiRelation;
	data_sources: TJsonApiRelation;
	tabs: TJsonApiRelation;
	groups: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IWidgetResponseModel extends TJsonaModel {
	id: string;
	type: IWidgetMeta;

	identifier: string;
	name: string | null;
	comment: string | null;

	owner: string | null;

	// Relations
	display: IPlainRelation | IWidgetDisplayResponseModel;
	dataSources: (IPlainRelation | IWidgetDataSourceResponseModel)[];
	tabs: (IPlainRelation | ITabResponseModel)[];
	groups: (IPlainRelation | IGroupResponseModel)[];
}

// DATABASE
// ========

export interface IWidgetDatabaseRecord {
	id: string;
	type: IWidgetMeta;

	identifier: string;
	name: string | null;
	comment: string | null;

	// Relations
	relationshipNames: RelationshipName[];

	display: IPlainRelation;
	dataSources: IPlainRelation[];
	tabs: IPlainRelation[];
	groups: IPlainRelation[];

	owner: string | null;
}
