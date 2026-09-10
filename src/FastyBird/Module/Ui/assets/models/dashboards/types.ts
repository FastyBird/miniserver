import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import { IEntityMeta, IPlainRelation, ITabResponseData, ITabResponseModel } from '../../models/types';
import { DashboardDocument } from '../../types';

export interface IDashboardMeta extends IEntityMeta {
	entity: 'dashboard';
}

type RelationshipName = 'tabs';

// STORE
// =====

export interface IDashboardsState {
	semaphore: IDashboardsStateSemaphore;
	firstLoad: boolean;
	data: { [key: IDashboard['id']]: IDashboard } | undefined;
	meta: { [key: IDashboard['id']]: IDashboardMeta };
}

export interface IDashboardsGetters extends _GettersTree<IDashboardsState> {
	firstLoadFinished: (state: IDashboardsState) => () => boolean;
	getting: (state: IDashboardsState) => (id: IDashboard['id']) => boolean;
	fetching: (state: IDashboardsState) => () => boolean;
	findById: (state: IDashboardsState) => (id: IDashboard['id']) => IDashboard | null;
	findAll: (state: IDashboardsState) => () => IDashboard[];
	findMeta: (state: IDashboardsState) => (id: IDashboard['id']) => IDashboardMeta | null;
}

export interface IDashboardsActions {
	set: (payload: IDashboardsSetActionPayload) => Promise<IDashboard>;
	get: (payload: IDashboardsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IDashboardsFetchActionPayload) => Promise<boolean>;
	add: (payload: IDashboardsAddActionPayload) => Promise<IDashboard>;
	edit: (payload: IDashboardsEditActionPayload) => Promise<IDashboard>;
	save: (payload: IDashboardsSaveActionPayload) => Promise<IDashboard>;
	remove: (payload: IDashboardsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IDashboardsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IDashboardsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IDashboardsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: () => Promise<boolean>;
}

// STORE STATE
// ===========

interface IDashboardsStateSemaphore {
	fetching: IDashboardsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IDashboardsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IDashboard {
	id: string;
	type: IDashboardMeta;

	draft: boolean;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	tabs: IPlainRelation[];

	owner: string | null;

	// Transformer transformers
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IDashboardRecordFactoryPayload {
	id?: string;
	type: IDashboardMeta;

	identifier: string;
	name: string | null;
	comment?: string | null;
	priority?: number;

	// Relations
	relationshipNames?: RelationshipName[];

	tabs?: (IPlainRelation | ITabResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IDashboardsSetActionPayload {
	data: IDashboardRecordFactoryPayload;
}

export interface IDashboardsGetActionPayload {
	id: IDashboard['id'];
	refresh?: boolean;
}

export interface IDashboardsFetchActionPayload {
	refresh?: boolean;
}

export interface IDashboardsAddActionPayload {
	id?: IDashboard['id'];
	type: IDashboardMeta;

	draft?: IDashboard['draft'];

	data: {
		identifier: IDashboard['identifier'];
		name: IDashboard['name'];
		comment?: IDashboard['comment'];
		priority?: IDashboard['priority'];
	};
}

export interface IDashboardsEditActionPayload {
	id: IDashboard['id'];

	data: {
		name?: IDashboard['name'];
		comment?: IDashboard['comment'];
		priority?: IDashboard['priority'];
	};
}

export interface IDashboardsSaveActionPayload {
	id: IDashboard['id'];
}

export interface IDashboardsRemoveActionPayload {
	id: IDashboard['id'];
}

export interface IDashboardsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IDashboardsInsertDataActionPayload {
	data: DashboardDocument | DashboardDocument[];
}

export interface IDashboardsLoadRecordActionPayload {
	id: IDashboard['id'];
}

// API RESPONSES JSONS
// ===================

export interface IDashboardResponseJson extends TJsonApiBody {
	data: IDashboardResponseData;
	included?: ITabResponseData[];
}

export interface IDashboardsResponseJson extends TJsonApiBody {
	data: IDashboardResponseData[];
	included?: ITabResponseData[];
}

export interface IDashboardResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IDashboardResponseDataAttributes;
	relationships: IDashboardResponseDataRelationships;
}

interface IDashboardResponseDataAttributes {
	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;
}

interface IDashboardResponseDataRelationships extends TJsonApiRelationships {
	tabs: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IDashboardResponseModel extends TJsonaModel {
	id: string;
	type: IDashboardMeta;

	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;

	// Relations
	tabs: (IPlainRelation | ITabResponseModel)[];
}

// DATABASE
// ========

export interface IDashboardDatabaseRecord {
	id: string;
	type: IDashboardMeta;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	tabs: IPlainRelation[];

	owner: string | null;
}
