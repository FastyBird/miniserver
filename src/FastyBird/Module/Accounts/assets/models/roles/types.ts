import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import { IAccountResponseData, IEntityMeta, IPlainRelation } from '../../types';

export interface IRoleMeta extends IEntityMeta {
	entity: 'role';
}

// STORE
// =====

export interface IRolesState {
	semaphore: Ref<IRolesStateSemaphore>;
	firstLoad: Ref<boolean>;
	data: Ref<{ [key: IRole['id']]: IRole }>;
}

export interface IRolesActions {
	// Getters
	firstLoadFinished: () => boolean;
	getting: (id: IRole['id']) => boolean;
	fetching: () => boolean;
	// Actions
	get: (payload: IRolesGetActionPayload) => Promise<boolean>;
	fetch: () => Promise<boolean>;
	add: (payload: IRolesAddActionPayload) => Promise<IRole>;
	edit: (payload: IRolesEditActionPayload) => Promise<IRole>;
	save: (payload: IRolesSaveActionPayload) => Promise<IRole>;
	remove: (payload: IRolesRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IRolesSocketDataActionPayload) => Promise<boolean>;
}

export type RolesStoreSetup = IRolesState & IRolesActions;

// STORE STATE
// ===========

export interface IRolesStateSemaphore {
	fetching: IRolesStateSemaphoreFetching;
	creating: IRole['id'][];
	updating: IRole['id'][];
	deleting: IRole['id'][];
}

interface IRolesStateSemaphoreFetching {
	items: boolean;
	item: IRole['id'][];
}

// STORE MODELS
// ============

export interface IRole {
	id: string;
	type: IRoleMeta;

	draft: boolean;

	name: string;
	description: string | null;

	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;

	// Relations
	relationshipNames: string[];

	accounts: IPlainRelation[];
}

// STORE DATA FACTORIES
// ====================

export interface IRoleRecordFactoryPayload {
	id?: IRole['id'];
	type: IRole['type'];

	draft?: IRole['draft'];

	name: IRole['name'];
	description?: IRole['description'];

	anonymous?: IRole['anonymous'];
	authenticated?: IRole['authenticated'];
	administrator?: IRole['administrator'];

	// Relations
	relationshipNames?: IRole['relationshipNames'];
}

// STORE ACTIONS
// =============

export interface IRolesGetActionPayload {
	id: IRole['id'];
}

export interface IRolesAddActionPayload {
	id?: IRole['id'];
	type: IRoleMeta;

	draft?: IRole['draft'];

	data: {
		name: IRole['name'];
		description?: IRole['description'];

		anonymous?: IRole['anonymous'];
		authenticated?: IRole['authenticated'];
		administrator?: IRole['administrator'];
	};
}

export interface IRolesEditActionPayload {
	id: IRole['id'];

	data: {
		name: IRole['name'];
		description?: IRole['description'];

		anonymous?: IRole['anonymous'];
		authenticated?: IRole['authenticated'];
		administrator?: IRole['administrator'];
	};
}

export interface IRolesSaveActionPayload {
	id: IRole['id'];
}

export interface IRolesRemoveActionPayload {
	id: IRole['id'];
}

export interface IRolesSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

// API RESPONSES JSONS
// ===================

export interface IRoleResponseJson extends TJsonApiBody {
	data: IRoleResponseData;
	includes?: IAccountResponseData[];
}

export interface IRolesResponseJson extends TJsonApiBody {
	data: IRoleResponseData[];
	includes?: IAccountResponseData[];
}

export interface IRoleResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IRoleResponseDataAttributes;
	relationships: IRoleResponseDataRelationships;
}

interface IRoleResponseDataAttributes {
	name: string;
	description: string | null;
	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;
}

interface IRoleResponseDataRelationships extends TJsonApiRelationships {
	accounts: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IRoleResponseModel extends TJsonaModel {
	id: string;
	type: IRoleMeta;

	name: string;
	description: string | null;

	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;

	// Relations
	relationshipNames: string[];

	accounts: IPlainRelation[];
}
