import { _GettersTree } from 'pinia';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from 'jsona/lib/JsonaTypes';

import { IEntityMeta, IPlainRelation, IWidget, IWidgetResponseData, IWidgetResponseModel } from '../../models/types';
import { WidgetDataSourceDocument } from '../../types';

export interface IWidgetDataSourceMeta extends IEntityMeta {
	type: string;
	entity: 'data-source';
}

type RelationshipName = 'widget';

// STORE
// =====

export interface IWidgetDataSourcesState {
	semaphore: IWidgetDataSourcesStateSemaphore;
	data: { [key: IWidgetDataSource['id']]: IWidgetDataSource } | undefined;
	meta: { [key: IWidgetDataSource['id']]: IWidgetDataSourceMeta };
}

export interface IWidgetDataSourcesGetters extends _GettersTree<IWidgetDataSourcesState> {
	getting: (state: IWidgetDataSourcesState) => (id: IWidgetDataSource['id']) => boolean;
	fetching: (state: IWidgetDataSourcesState) => (widgetId: IWidget['id'] | null) => boolean;
	findById: (state: IWidgetDataSourcesState) => (id: IWidgetDataSource['id']) => IWidgetDataSource | null;
	findForWidget: (state: IWidgetDataSourcesState) => (widgetId: IWidget['id']) => IWidgetDataSource[];
	findMeta: (state: IWidgetDataSourcesState) => (id: IWidgetDataSource['id']) => IWidgetDataSourceMeta | null;
}

export interface IWidgetDataSourcesActions {
	set: (payload: IWidgetDataSourcesSetActionPayload) => Promise<IWidgetDataSource>;
	unset: (payload: IWidgetDataSourcesUnsetActionPayload) => void;
	get: (payload: IWidgetDataSourcesGetActionPayload) => Promise<boolean>;
	fetch: (payload: IWidgetDataSourcesFetchActionPayload) => Promise<boolean>;
	add: (payload: IWidgetDataSourcesAddActionPayload) => Promise<IWidgetDataSource>;
	edit: (payload: IWidgetDataSourceEditActionPayload) => Promise<IWidgetDataSource>;
	save: (payload: IWidgetDataSourcesSaveActionPayload) => Promise<IWidgetDataSource>;
	remove: (payload: IWidgetDataSourcesRemoveActionPayload) => Promise<boolean>;
	transmitCommand: (payload: IWidgetDataSourcesTransmitCommandActionPayload) => Promise<boolean>;
	socketData: (payload: IWidgetDataSourcesSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IWidgetDataSourcesInsertDataActionPayload) => Promise<boolean>;
	loadRecord: (payload: IWidgetDataSourcesLoadRecordActionPayload) => Promise<boolean>;
	loadAllRecords: (payload?: IWidgetDataSourcesLoadAllRecordsActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

export interface IWidgetDataSourcesStateSemaphore {
	fetching: IWidgetDataSourcesStateSemaphoreFetching;
	creating: string[];
	updating: string[];
	deleting: string[];
}

interface IWidgetDataSourcesStateSemaphoreFetching {
	items: string[];
	item: string[];
}

// STORE MODELS
// ============

export interface IWidgetDataSource {
	id: string;
	type: IWidgetDataSourceMeta;

	draft: boolean;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IWidgetDataSourceRecordFactoryPayload {
	id?: string;
	type: IWidgetDataSourceMeta;

	draft?: boolean;

	params?: object;

	// Relations
	relationshipNames?: RelationshipName[];

	widgetId?: string;
	widget?: IPlainRelation;
}

// STORE ACTIONS
// =============

export interface IWidgetDataSourcesSetActionPayload {
	data: IWidgetDataSourceRecordFactoryPayload;
}

export interface IWidgetDataSourcesUnsetActionPayload {
	widget?: IWidget;
	id?: IWidgetDataSource['id'];
}

export interface IWidgetDataSourcesGetActionPayload {
	widget: IWidget;
	id: IWidgetDataSource['id'];
	refresh?: boolean;
}

export interface IWidgetDataSourcesFetchActionPayload {
	widget: IWidget;
	refresh?: boolean;
}

export interface IWidgetDataSourcesAddActionPayload {
	id?: IWidgetDataSource['id'];
	type: IWidgetDataSourceMeta;

	draft?: IWidgetDataSource['draft'];

	data: {
		params?: object;
	};

	widget: IWidget;
}

export interface IWidgetDataSourceEditActionPayload {
	id: IWidget['id'];

	data: {
		params?: object;
	};

	widget: IWidget;
}

export interface IWidgetDataSourcesSaveActionPayload {
	id: IWidgetDataSource['id'];
}

export interface IWidgetDataSourcesRemoveActionPayload {
	id: IWidgetDataSource['id'];
}

export interface IWidgetDataSourcesTransmitCommandActionPayload {
	id: IWidgetDataSource['id'];
	value?: string;
}

export interface IWidgetDataSourcesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IWidgetDataSourcesInsertDataActionPayload {
	data: WidgetDataSourceDocument | WidgetDataSourceDocument[];
}

export interface IWidgetDataSourcesLoadRecordActionPayload {
	id: IWidgetDataSource['id'];
}

export interface IWidgetDataSourcesLoadAllRecordsActionPayload {
	widget: IWidget;
}

// API RESPONSES JSONS
// ===================

export interface IWidgetDataSourceResponseJson extends TJsonApiBody {
	data: IWidgetDataSourceResponseData;
	includes?: IWidgetResponseData[];
}

export interface IWidgetDataSourcesResponseJson extends TJsonApiBody {
	data: IWidgetDataSourceResponseData[];
	includes?: IWidgetResponseData[];
}

export interface IWidgetDataSourceResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IWidgetDataSourceResponseDataAttributes;
	relationships: IWidgetDataSourceResponseDataRelationships;
}

interface IWidgetDataSourceResponseDataAttributes {
	params: object;
}

interface IWidgetDataSourceResponseDataRelationships extends TJsonApiRelationships {
	widget: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IWidgetDataSourceResponseModel extends TJsonaModel {
	id: string;
	type: IWidgetDataSourceMeta;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation | IWidgetResponseModel;
}

// DATABASE
// ========

export interface IWidgetDataSourceDatabaseRecord {
	id: string;
	type: IWidgetDataSourceMeta;

	params: object;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}
