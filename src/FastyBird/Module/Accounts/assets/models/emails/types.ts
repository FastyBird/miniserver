import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import { EmailDocument, IAccount, IAccountResponseData, IEntityMeta, IPlainRelation } from '../../types';

export interface IEmailMeta extends IEntityMeta {
	entity: 'email';
}

// STORE
// =====

export interface IEmailsState {
	semaphore: Ref<IEmailsStateSemaphore>;
	firstLoad: Ref<IEmail['id'][]>;
	data: Ref<{ [key: IEmail['id']]: IEmail }>;
}

export interface IEmailsActions {
	// Getters
	firstLoadFinished: (accountId: IAccount['id']) => boolean;
	getting: (id: IEmail['id']) => boolean;
	fetching: (accountId: IAccount['id'] | null) => boolean;
	findById: (id: IEmail['id']) => IEmail | null;
	findByAddress: (address: IEmail['address']) => IEmail | null;
	findForAccount: (accountId: IAccount['id']) => IEmail[];
	// Actions
	set: (payload: IEmailsSetActionPayload) => Promise<IEmail>;
	unset: (payload: IEmailsUnsetActionPayload) => void;
	get: (payload: IEmailsGetActionPayload) => Promise<boolean>;
	fetch: (payload: IEmailsFetchActionPayload) => Promise<boolean>;
	add: (payload: IEmailsAddActionPayload) => Promise<IEmail>;
	edit: (payload: IEmailsEditActionPayload) => Promise<IEmail>;
	save: (payload: IEmailsSaveActionPayload) => Promise<IEmail>;
	remove: (payload: IEmailsRemoveActionPayload) => Promise<boolean>;
	validate: (payload: IEmailsValidateActionPayload) => Promise<any>;
	socketData: (payload: IEmailsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IEmailsInsertDataActionPayload) => Promise<boolean>;
}

export type EmailsStoreSetup = IEmailsState & IEmailsActions;

// STORE STATE
// ===========

export interface IEmailsStateSemaphore {
	fetching: IEmailsStateSemaphoreFetching;
	creating: IEmail['id'][];
	updating: IEmail['id'][];
	deleting: IEmail['id'][];
}

interface IEmailsStateSemaphoreFetching {
	items: IAccount['id'][];
	item: IEmail['id'][];
}

// STORE MODELS
// ============

export interface IEmail {
	id: string;
	type: IEmailMeta;

	draft: boolean;

	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;

	// Entity transformers
	isDefault: boolean;
	isPrivate: boolean;
	isVerified: boolean;
}

// STORE DATA FACTORIES
// ====================

export interface IEmailRecordFactoryPayload {
	id?: IEmail['id'];
	type: IEmailMeta;

	draft?: IEmail['draft'];

	address: IEmail['address'];
	default?: IEmail['default'];
	private?: IEmail['private'];
	verified?: IEmail['verified'];

	// Relations
	relationshipNames?: IEmail['relationshipNames'];

	accountId: IAccount['id'];
}

// STORE ACTIONS
// =============

export interface IEmailsSetActionPayload {
	data: IEmailRecordFactoryPayload;
}

export interface IEmailsUnsetActionPayload {
	account?: IAccount;
	id?: IEmail['id'];
}

export interface IEmailsGetActionPayload {
	account: IAccount;
	id: IEmail['id'];
}

export interface IEmailsFetchActionPayload {
	account: IAccount;
}

export interface IEmailsAddActionPayload {
	id?: IEmail['id'];
	type: IEmailMeta;

	draft?: IEmail['draft'];

	account: IAccount;

	data: {
		address: IEmail['address'];
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IEmailsEditActionPayload {
	id: IEmail['id'];

	data: {
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IEmailsSaveActionPayload {
	id: IEmail['id'];
}

export interface IEmailsRemoveActionPayload {
	id: IEmail['id'];
}

export interface IEmailsValidateActionPayload {
	address: IEmail['address'];
}

export interface IEmailsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IEmailsInsertDataActionPayload {
	data: EmailDocument | EmailDocument[];
}

// API RESPONSES JSONS
// ===================

export interface IEmailResponseJson extends TJsonApiBody {
	data: IEmailResponseData;
	includes?: IAccountResponseData[];
}

export interface IEmailsResponseJson extends TJsonApiBody {
	data: IEmailResponseData[];
	includes?: IAccountResponseData[];
}

export interface IEmailResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IEmailResponseDataAttributes;
	relationships: IEmailResponseDataRelationships;
}

interface IEmailResponseDataAttributes {
	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;
}

interface IEmailResponseDataRelationships extends TJsonApiRelationships {
	account: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IEmailResponseModel extends TJsonaModel {
	id: string;
	type: IEmailMeta;

	address: string;
	default: boolean;
	private: boolean;
	verified: boolean;

	// Relations
	relationshipNames: string[];

	account: IPlainRelation;
}
