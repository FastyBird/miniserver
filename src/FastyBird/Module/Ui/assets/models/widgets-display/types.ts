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
	// Identifiers of the widgets whose display is being fetched
	items: string[];
}

// STORE MODELS
// ============

// Display parameters, flattened the way the API and the exchange documents carry them.
// Each display type uses a subset of them; the others stay null.
export interface IWidgetDisplayParameters {
	minimumValue: number | null;
	maximumValue: number | null;
	stepValue: number | null;
	precision: number | null;
	enableMinMax: boolean | null;
	icon: string | null;
}

export interface IWidgetDisplay extends IWidgetDisplayParameters {
	id: string;
	type: IWidgetDisplayMeta;

	draft: boolean;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}

// STORE DATA FACTORIES
// ====================

export interface IWidgetDisplayRecordFactoryPayload extends Partial<IWidgetDisplayParameters> {
	id?: string;
	type: IWidgetDisplayMeta;

	draft?: boolean;

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

// The display is a to-one resource of the widget, so it is addressed by the widget alone
export interface IWidgetDisplayGetActionPayload {
	widget: IWidget;
	refresh?: boolean;
}

export interface IWidgetDisplayEditActionPayload {
	id: IWidgetDisplay['id'];

	data: Partial<IWidgetDisplayParameters>;
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
	included?: IWidgetResponseData[];
}

export interface IWidgetDisplayResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IWidgetDisplayResponseDataAttributes;
	relationships: IWidgetDisplayResponseDataRelationships;
}

interface IWidgetDisplayResponseDataAttributes {
	// Raw parameter storage the schema also sends. The store reads the flattened attributes.
	params?: object;

	minimum_value?: number | null;
	maximum_value?: number | null;
	step_value?: number | null;
	precision?: number | null;
	enable_min_max?: boolean | null;
	icon?: string | null;
}

interface IWidgetDisplayResponseDataRelationships extends TJsonApiRelationships {
	widget: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IWidgetDisplayResponseModel extends TJsonaModel, Partial<IWidgetDisplayParameters> {
	id: string;
	type: IWidgetDisplayMeta;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation | IWidgetResponseModel;
}

// DATABASE
// ========

export interface IWidgetDisplayDatabaseRecord extends IWidgetDisplayParameters {
	id: string;
	type: IWidgetDisplayMeta;

	// Relations
	relationshipNames: RelationshipName[];

	widget: IPlainRelation;
}
