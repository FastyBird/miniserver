import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import {
	IDashboardResponseData,
	IDashboardResponseModel,
	IEntityMeta,
	IPlainRelation,
	IWidgetResponseData,
	IWidgetResponseModel,
} from '../../models/types';
import { TabDocument } from '../../types';

export interface ITabMeta extends IEntityMeta {
	entity: 'tab';
}

type RelationshipName = 'widgets' | 'dashboard';

// STORE
// =====

export interface ITabsState {
	semaphore: ITabsStateSemaphore;
	firstLoad: boolean;
	data: { [key: ITab['id']]: ITab } | undefined;
	meta: { [key: ITab['id']]: ITabMeta };
}

export interface ITabsGetters extends _GettersTree<ITabsState> {
	firstLoadFinished: (state: ITabsState) => () => boolean;
	getting: (state: ITabsState) => (id: ITab['id']) => boolean;
	fetching: (state: ITabsState) => () => boolean;
	findById: (state: ITabsState) => (id: ITab['id']) => ITab | null;
	findAll: (state: ITabsState) => () => ITab[];
	findMeta: (state: ITabsState) => (id: ITab['id']) => ITabMeta | null;
}

export interface ITabsActions {
	set: (payload: ITabsSetActionPayload) => Promise<ITab>;
	get: (payload: ITabsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: ITabsFetchActionPayload) => Promise<boolean>;
	add: (payload: ITabsAddActionPayload) => Promise<ITab>;
	edit: (payload: ITabsEditActionPayload) => Promise<ITab>;
	save: (payload: ITabsSaveActionPayload) => Promise<ITab>;
	remove: (payload: ITabsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: ITabsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: ITabsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: ITabsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: () => Promise<boolean>;
}

// STORE STATE
// ===========

interface ITabsStateSemaphore {
	fetching: ITabsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface ITabsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface ITab {
	id: string;
	type: ITabMeta;

	draft: boolean;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	dashboard: IPlainRelation;
	widgets: IPlainRelation[];

	owner: string | null;

	// Transformer transformers
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface ITabRecordFactoryPayload {
	id?: string;
	type: ITabMeta;

	identifier: string;
	name: string | null;
	comment?: string | null;
	priority?: number;

	// Relations
	relationshipNames?: RelationshipName[];

	dashboard?: IPlainRelation | IDashboardResponseModel;
	widgets?: (IPlainRelation | IWidgetResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface ITabsSetActionPayload {
	data: ITabRecordFactoryPayload;
}

export interface ITabsGetActionPayload {
	id: ITab['id'];
	refresh?: boolean;
}

export interface ITabsFetchActionPayload {
	refresh?: boolean;
}

export interface ITabsAddActionPayload {
	id?: ITab['id'];
	type: ITabMeta;

	draft?: ITab['draft'];

	data: {
		identifier: ITab['identifier'];
		name: ITab['name'];
		comment?: ITab['comment'];
		priority?: ITab['priority'];
	};
}

export interface ITabsEditActionPayload {
	id: ITab['id'];

	data: {
		name?: ITab['name'];
		comment?: ITab['comment'];
		priority?: ITab['priority'];
	};
}

export interface ITabsSaveActionPayload {
	id: ITab['id'];
}

export interface ITabsRemoveActionPayload {
	id: ITab['id'];
}

export interface ITabsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface ITabsInsertDataActionPayload {
	data: TabDocument | TabDocument[];
}

export interface ITabsLoadRecordActionPayload {
	id: ITab['id'];
}

// API RESPONSES JSONS
// ===================

export interface ITabResponseJson extends TJsonApiBody {
	data: ITabResponseData;
	included?: IWidgetResponseData[] | IDashboardResponseData[];
}

export interface ITabsResponseJson extends TJsonApiBody {
	data: ITabResponseData[];
	included?: IWidgetResponseData[] | IDashboardResponseData[];
}

export interface ITabResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: ITabResponseDataAttributes;
	relationships: ITabResponseDataRelationships;
}

interface ITabResponseDataAttributes {
	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;
}

interface ITabResponseDataRelationships extends TJsonApiRelationships {
	dashboard: TJsonApiRelation;
	widgets: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface ITabResponseModel extends TJsonaModel {
	id: string;
	type: ITabMeta;

	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;

	// Relations
	dashboard: IPlainRelation | IDashboardResponseModel;
	widgets: (IPlainRelation | IWidgetResponseModel)[];
}

// DATABASE
// ========

export interface ITabDatabaseRecord {
	id: string;
	type: ITabMeta;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	dashboard: IPlainRelation;
	widgets: IPlainRelation[];

	owner: string | null;
}
