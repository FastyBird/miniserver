import { TJsonaModel } from '@fastybird/miniserver-core';

import { IEntityMeta } from '../types';

export interface IControlMeta extends IEntityMeta {
	parent: 'connector' | 'device' | 'channel';
	entity: 'control';
}

// STORE MODELS
// ============

export interface IControl {
	id: string;
	type: IControlMeta;

	draft: boolean;

	name: string;

	// Relations
	relationshipNames: string[];
}

// STORE DATA FACTORIES
// ====================

export interface IControlRecordFactoryPayload {
	id?: string;
	type: IControlMeta;

	draft?: boolean;

	name: string;

	// Relations
	relationshipNames?: string[];
}

// STORE ACTIONS
// =============

export interface IControlsAddActionPayload {
	id?: IControl['id'];
	type: IControlMeta;

	draft?: IControl['draft'];

	data: {
		name: IControl['name'];
	};
}

// API RESPONSE MODELS
// ===================

export interface IControlResponseModel extends TJsonaModel {
	id: string;
	type: IControlMeta;

	name: string;

	// Relations
	relationshipNames: string[];
}

// DATABASE
// ========

export interface IControlDatabaseRecord {
	id: string;
	type: IControlMeta;

	name: string;

	// Relations
	relationshipNames: string[];
}
