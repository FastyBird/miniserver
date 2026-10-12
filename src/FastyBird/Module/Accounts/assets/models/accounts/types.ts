import { Ref } from 'vue';

import { TJsonApiBody, TJsonApiData, TJsonApiRelation, TJsonApiRelationships, TJsonaModel } from '@fastybird/miniserver-core';

import {
	AccountDocument,
	AccountState,
	IEmail,
	IEmailResponseData,
	IEmailResponseModel,
	IEntityMeta,
	IIdentityResponseData,
	IIdentityResponseModel,
	IPlainRelation,
	IRoleResponseModel,
} from '../../types';

export interface IAccountMeta extends IEntityMeta {
	entity: 'account';
}

// STORE
// =====

export interface IAccountsState {
	semaphore: Ref<IAccountsStateSemaphore>;
	firstLoad: Ref<boolean>;
	data: Ref<{ [key: IAccount['id']]: IAccount }>;
}

export interface IAccountsActions {
	// Getters
	findById: (id: IAccount['id']) => IAccount | null;
	// Actions
	set: (payload: IAccountsSetActionPayload) => Promise<IAccount>;
	get: (payload: IAccountsGetActionPayload) => Promise<boolean>;
	fetch: () => Promise<boolean>;
	add: (payload: IAccountsAddActionPayload) => Promise<IAccount>;
	edit: (payload: IAccountsEditActionPayload) => Promise<IAccount>;
	save: (payload: IAccountsSaveActionPayload) => Promise<IAccount>;
	remove: (payload: IAccountsRemoveActionPayload) => Promise<boolean>;
	socketData: (payload: IAccountsSocketDataActionPayload) => Promise<boolean>;
	insertData: (payload: IAccountsInsertDataActionPayload) => Promise<boolean>;
}

export type AccountsStoreSetup = IAccountsState & IAccountsActions;

// STORE STATE
// ===========

export interface IAccountsStateSemaphore {
	fetching: IAccountsStateSemaphoreFetching;
	creating: IAccount['id'][];
	updating: IAccount['id'][];
	deleting: IAccount['id'][];
}

export interface IAccountsStateSemaphoreFetching {
	items: boolean;
	item: IAccount['id'][];
}

export interface IAccount {
	id: string;
	type: IAccountMeta;

	draft: boolean;

	details: {
		firstName: string;
		lastName: string;
		middleName: string | null;
	};

	language: string;

	weekStart: number;
	dateTime: {
		timezone: string;
		dateFormat: string;
		timeFormat: string;
	};

	state: AccountState;

	lastVisit: string | null;
	registered: string | null;

	// Relations
	relationshipNames: string[];

	emails: IPlainRelation[];
	identities: IPlainRelation[];
	roles: IPlainRelation[];

	// Entity transformers
	name: string;
	email?: IEmail | null;
}

// STORE DATA FACTORIES
// ====================

export interface IAccountRecordFactoryPayload {
	id?: IAccount['id'];
	type: IAccountMeta;

	details: {
		firstName: IAccount['details']['firstName'];
		lastName: IAccount['details']['lastName'];
		middleName?: IAccount['details']['middleName'];
	};

	language?: IAccount['language'];

	weekStart?: IAccount['weekStart'];
	dateTime?: {
		timezone?: IAccount['dateTime']['timezone'];
		dateFormat?: IAccount['dateTime']['dateFormat'];
		timeFormat?: IAccount['dateTime']['timeFormat'];
	};

	state?: IAccount['state'];

	lastVisit?: IAccount['lastVisit'];
	registered?: IAccount['registered'];

	// Relations
	relationshipNames?: IAccount['relationshipNames'];

	emails?: (IPlainRelation | IEmailResponseModel)[];
	identities?: (IPlainRelation | IIdentityResponseModel)[];
	roles?: (IPlainRelation | IRoleResponseModel)[];
}

// STORE ACTIONS
// =============

export interface IAccountsSetActionPayload {
	data: IAccountRecordFactoryPayload;
}

export interface IAccountsGetActionPayload {
	id: IAccount['id'];
}

export interface IAccountsAddActionPayload {
	id?: IAccount['id'];
	type: IAccountMeta;

	draft?: IAccount['draft'];

	data: {
		details: {
			firstName: IAccount['details']['firstName'];
			lastName: IAccount['details']['lastName'];
			middleName?: IAccount['details']['middleName'];
		};

		language?: IAccount['language'];

		weekStart?: IAccount['weekStart'];
		dateTime: {
			timezone?: IAccount['dateTime']['timezone'];
			dateFormat?: IAccount['dateTime']['dateFormat'];
			timeFormat?: IAccount['dateTime']['timeFormat'];
		};
	};
}

export interface IAccountsEditActionPayload {
	id: IAccount['id'];

	data: {
		details: {
			firstName: IAccount['details']['firstName'];
			lastName: IAccount['details']['lastName'];
			middleName?: IAccount['details']['middleName'];
		};

		language?: IAccount['language'];

		weekStart?: IAccount['weekStart'];
		dateTime: {
			timezone?: IAccount['dateTime']['timezone'];
			dateFormat?: IAccount['dateTime']['dateFormat'];
			timeFormat?: IAccount['dateTime']['timeFormat'];
		};
	};
}

export interface IAccountsSaveActionPayload {
	id: IAccount['id'];
}

export interface IAccountsRemoveActionPayload {
	id: IAccount['id'];
}

export interface IAccountsSocketDataActionPayload {
	source: string;
	routingKey: string;
	data: string;
}

export interface IAccountsInsertDataActionPayload {
	data: AccountDocument | AccountDocument[];
}

// API RESPONSES JSONS
// ===================

export interface IAccountResponseJson extends TJsonApiBody {
	data: IAccountResponseData;
	included?: (IEmailResponseData | IIdentityResponseData)[];
}

export interface IAccountsResponseJson extends TJsonApiBody {
	data: IAccountResponseData[];
	included?: (IEmailResponseData | IIdentityResponseData)[];
}

export interface IAccountResponseData extends TJsonApiData {
	id: string;
	type: string;
	attributes: IAccountResponseDataAttributes;
	relationships: IAccountResponseDataRelationships;
}

interface IAccountResponseDataAttributes {
	details: IAccountResponseDataAttributesDetails;

	week_start: number;
	datetime: IAccountResponseDataAttributesDatetime;

	language: string;

	state: AccountState;

	last_visit: string | null;
	registered: string | null;
}

interface IAccountResponseDataAttributesDetails {
	first_name: string;
	last_name: string;
	middle_name: string | null;
}

interface IAccountResponseDataAttributesDatetime {
	timezone: string;
	date_format: string;
	time_format: string;
}

interface IAccountResponseDataRelationships extends TJsonApiRelationships {
	emails: TJsonApiRelation;
	identities: TJsonApiRelation;
	roles: TJsonApiRelation;
}

// API RESPONSE MODELS
// ===================

export interface IAccountResponseModel extends TJsonaModel {
	id: string;
	type: IAccountMeta;

	details: {
		firstName: string;
		lastName: string;
		middleName: string | null;
	};

	language: string;

	weekStart: number;
	dateTime: {
		timezone: string;
		dateFormat: string;
		timeFormat: string;
	};

	state: AccountState;

	lastVisit: string | null;
	registered: string | null;

	// Relations
	relationshipNames: string[];

	emails: (IPlainRelation | IEmailResponseModel)[];
	identities: (IPlainRelation | IIdentityResponseModel)[];
	roles: (IPlainRelation | IRoleResponseModel)[];
}
