import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import { IEntityMeta, IPlainRelation, IWidget, IWidgetResponseData, IWidgetResponseModel } from '../../models/types';
import { WidgetDisplayDocument } from '../../types';

export interface IWidgetDisplayMeta extends IEntityMeta {
	type: string;
	entity: 'display';
}

type RelationshipName = 'widget';

// STORE
// =====

export interface IWidgetDisplayState {
	semaphore: IWidgetDisplayStateSemaphore;
	data: { [key: IWidgetDisplay['id']]: IWidgetDisplay } | undefined;
	meta: { [key: IWidgetDisplay['id']]: IWidgetDisplayMeta };
}

export interface IWidgetDisplayGetters extends _GettersTree<IWidgetDisplayState> {
	getting: (state: IWidgetDisplayState) => (id: IWidgetDisplay['id']) => boolean;
	fetching: (state: IWidgetDisplayState) => (widgetId: IWidget['id'] | null) => boolean;
	findById: (state: IWidgetDisplayState) => (id: IWidgetDisplay['id']) => IWidgetDisplay | null;
	findForWidget: (state: IWidgetDisplayState) => (widgetId: IWidget['id']) => IWidgetDisplay[];
	findMeta: (state: IWidgetDisplayState) => (id: IWidgetDisplay['id']) => IWidgetDisplayMeta | null;
}

export interface IWidgetDisplayActions {
	set: (payload: IWidgetDisplaySetActionPayload) => Promise<IWidgetDisplay>;
	unset: (payload: IWidgetDisplayUnsetActionPayload) => void;
	get: (payload: IWidgetDisplayGetActionPayload) => Promise<boolean>;
	edit: (payload: IWidgetDisplayEditActionPayload) => Promise<IWidgetDisplay>;
	save: (payload: IWidgetDisplaySaveActionPayload) => Promise<IWidgetDisplay>;
	socketData: (payload: IWidgetDisplaySocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IWidgetDisplayInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IWidgetDisplayLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IWidgetDisplayLoadAllRecordsActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IWidgetDisplayStateSemaphore {
	fetching: IWidgetDisplayStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IWidgetDisplayStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IWidgetDisplay {
	id: string;
	type: IWidgetDisplayMeta;

	draft: boolean;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IWidgetDisplayRecordFactoryPayload {
	id?: string;
	type: IWidgetDisplayMeta;

	draft?: boolean;

	params?: object;

	// Relations
	relationshipNames?: RelationshipName[];

	widgetId?: string;
	widget?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IWidgetDisplaySetActionPayload {
	data: IWidgetDisplayRecordFactoryPayload;
}

export interface IWidgetDisplayUnsetActionPayload {
	widget?: IWidget;
	id?: IWidgetDisplay['id'];
}

export interface IWidgetDisplayGetActionPayload {
	widget: IWidget;
	id: IWidgetDisplay['id'];
	refresh?: boolean;
}

export interface IWidgetDisplayEditActionPayload {
	id: IWidget['id'];

	data: {
		params?: object;
	};

	widget: IWidget;
}

export interface IWidgetDisplaySaveActionPayload {
	id: IWidgetDisplay['id'];
}

export interface IWidgetDisplaySocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IWidgetDisplayInsertDataActionPayload {
	data: WidgetDisplayDocument | WidgetDisplayDocument[];
}

export interface IWidgetDisplayLoadRecordActionPayload {
	id: IWidgetDisplay['id'];
}

export interface IWidgetDisplayLoadAllRecordsActionPayload {
	widget: IWidget;
}

// API RESPONSES JSONS
// ===================

export interface IWidgetDisplayResponseJson extends TJsonApiBody {
	data: IWidgetDisplayResponseData;
	includes?: IWidgetResponseData[];
}

export interface IWidgetDisplayResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IWidgetDisplayResponseDataAttributes;
	relationships: IWidgetDisplayResponseDataRelationships;
}

interface IWidgetDisplayResponseDataAttributes {
	params: object;
}

interface IWidgetDisplayResponseDataRelationships extends TJsonApiRelationships {
	widget: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IWidgetDisplayResponseModel extends TJsonaModel {
	id: string;
	type: IWidgetDisplayMeta;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation | IWidgetResponseModel;
}

// DATABASE
// ========

export interface IWidgetDisplayDatabaseRecord {
	id: string;
	type: IWidgetDisplayMeta;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}
