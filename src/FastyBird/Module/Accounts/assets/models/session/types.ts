import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import { IAccount, IEntityMeta, IIdentity, IPlainRelation } from '../types';

export interface ISessionMeta extends IEntityMeta {
	entity: 'session';
}

// STORE
// =====

export interface ISessionState {
	semaphore: Ref<ISessionStateSemaphore>;
	data: Ref<ISession>;
}

export interface ISessionActions {
	// Getters
	accessToken: () => string | null;
	refreshToken: () => string | null;
	accountId: () => string | null;
	account: () => IAccount | null;
	isSignedIn: () => boolean;
	// Actions
	initialize: () => void;
	clear: () => void;
	fetch: () => Promise<boolean>;
	create: (payload: ISessionCreateActionPayload) => Promise<boolean>;
	refresh: () => Promise<boolean>;
}

export type SessionStoreSetup = ISessionState & ISessionActions;

// STORE STATE
// ===========

export interface ISessionStateSemaphore {
	fetching: boolean;
	creating: boolean;
	updating: boolean;
}

export interface ISession {
	refreshToken: string | null;
	accessToken: string | null;
	tokenExpiration: string | null;
	tokenType: string;

	accountId: IAccount['id'] | null;
}

// STORE DATA FACTORIES
// ====================

export interface ISessionRecordFactoryPayload {
	accessToken: string;
	refreshToken: string;
	tokenType: string;
}

// STORE ACTIONS
// =============

export interface ISessionCreateActionPayload {
	uid: IIdentity['uid'];
	password: IIdentity['password'];
}

// API RESPONSES JSONS
// ===================

export interface ISessionResponseJson extends TJsonApiBody {
	data: ISessionResponseData;
}

interface ISessionResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: ISessionResponseDataAttributes;
	relationships: ISessionResponseDataRelationships;
}

interface ISessionResponseDataAttributes {
	expiration: string;
	refresh: string;
	token: string;
	token_type: string;
}

interface ISessionResponseDataRelationships extends TJsonApiRelationships {
	account: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface ISessionResponseModel extends TJsonaModel {
	id: string;
	type: ISessionMeta;

	token: string;
	tokenType: string;
	expiration: string;
	refresh: string;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
