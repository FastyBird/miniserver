import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import { IEntityMeta, IPlainRelation, IWidgetResponseData, IWidgetResponseModel } from '../../models/types';
import { GroupDocument } from '../../types';

export interface IGroupMeta extends IEntityMeta {
	entity: 'group';
}

type RelationshipName = 'widgets';

// STORE
// =====

export interface IGroupsState {
	semaphore: IGroupsStateSemaphore;
	firstLoad: boolean;
	data: { [key: IGroup['id']]: IGroup } | undefined;
	meta: { [key: IGroup['id']]: IGroupMeta };
}

export interface IGroupsGetters extends _GettersTree<IGroupsState> {
	firstLoadFinished: (state: IGroupsState) => () => boolean;
	getting: (state: IGroupsState) => (id: IGroup['id']) => boolean;
	fetching: (state: IGroupsState) => () => boolean;
	findById: (state: IGroupsState) => (id: IGroup['id']) => IGroup | null;
	findAll: (state: IGroupsState) => () => IGroup[];
	findMeta: (state: IGroupsState) => (id: IGroup['id']) => IGroupMeta | null;
}

export interface IGroupsActions {
	set: (payload: IGroupsSetActionPayload) => Promise<IGroup>;
	get: (payload: IGroupsGetActionPayload) => Promise<boolean>;
	fetch: (payload?: IGroupsFetchActionPayload) => Promise<boolean>;
	add: (payload: IGroupsAddActionPayload) => Promise<IGroup>;
	edit: (payload: IGroupsEditActionPayload) => Promise<IGroup>;
	save: (payload: IGroupsSaveActionPayload) => Promise<IGroup>;
	remove: (payload: IGroupsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IGroupsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IGroupsInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IGroupsLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: () => Promise<boolean>;
}

// STORE STATE
// ===========

interface IGroupsStateSemaphore {
	fetching: IGroupsStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IGroupsStateSemaphoreFetching {
	items: boolean;
	item: string[];
}

export interface IGroup {
	id: string;
	type: IGroupMeta;

	draft: boolean;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	widgets: IPlainRelation[];

	owner: string | null;

	// Transformer transformers
	hasComment: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IGroupRecordFactoryPayload {
	id?: string;
	type: IGroupMeta;

	identifier: string;
	name: string | null;
	comment?: string | null;
	priority?: number;

	// Relations
	relationshipNames?: RelationshipName[];

	widgets?: (IPlainRelation | IWidgetResponseModel)[];

	owner?: string | null;
}

// STORE ACTIONS
// =============

export interface IGroupsSetActionPayload {
	data: IGroupRecordFactoryPayload;
}

export interface IGroupsGetActionPayload {
	id: IGroup['id'];
	refresh?: boolean;
}

export interface IGroupsFetchActionPayload {
	refresh?: boolean;
}

export interface IGroupsAddActionPayload {
	id?: IGroup['id'];
	type: IGroupMeta;

	draft?: IGroup['draft'];

	data: {
		identifier: IGroup['identifier'];
		name: IGroup['name'];
		comment?: IGroup['comment'];
		priority?: IGroup['priority'];
	};
}

export interface IGroupsEditActionPayload {
	id: IGroup['id'];

	data: {
		name?: IGroup['name'];
		comment?: IGroup['comment'];
		priority?: IGroup['priority'];
	};
}

export interface IGroupsSaveActionPayload {
	id: IGroup['id'];
}

export interface IGroupsRemoveActionPayload {
	id: IGroup['id'];
}

export interface IGroupsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IGroupsInsertDataActionPayload {
	data: GroupDocument | GroupDocument[];
}

export interface IGroupsLoadRecordActionPayload {
	id: IGroup['id'];
}

// API RESPONSES JSONS
// ===================

export interface IGroupResponseJson extends TJsonApiBody {
	data: IGroupResponseData;
	included?: IWidgetResponseData[];
}

export interface IGroupsResponseJson extends TJsonApiBody {
	data: IGroupResponseData[];
	included?: IWidgetResponseData[];
}

export interface IGroupResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IGroupResponseDataAttributes;
	relationships: IGroupResponseDataRelationships;
}

interface IGroupResponseDataAttributes {
	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;
}

interface IGroupResponseDataRelationships extends TJsonApiRelationships {
	widgets: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IGroupResponseModel extends TJsonaModel {
	id: string;
	type: IGroupMeta;

	identifier: string;
	name: string | null;
	comment: string | null;

	priority: number;

	owner: string | null;

	// Relations
	widgets: (IPlainRelation | IWidgetResponseModel)[];
}

// DATABASE
// ========

export interface IGroupDatabaseRecord {
	id: string;
	type: IGroupMeta;

	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;

	// Relations
	relationshipNames: RelationshipName[];

	widgets: IPlainRelation[];

	owner: string | null;
}
