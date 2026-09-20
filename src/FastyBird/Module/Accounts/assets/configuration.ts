import { InjectionKey } from 'vue';

import { StoreInjectionKey } from '@fastybird/miniserver-core';

import {
	IAccountActions,
	IAccountState,
	IAccountsActions,
	IAccountsModuleMeta,
	IAccountsState,
	IEmailsActions,
	IEmailsState,
	IIdentitiesActions,
	IIdentitiesState,
	IRolesActions,
	IRolesState,
	ISessionActions,
	ISessionState,
} from './types';

export const metaKey: InjectionKey<IAccountsModuleMeta> = Symbol('accounts-module_meta');

export const accountStoreKey: StoreInjectionKey<string, IAccountState, object, IAccountActions> = Symbol('accounts-module_store_account');

export const accountsStoreKey: StoreInjectionKey<string, IAccountsState, object, IAccountsActions> = Symbol('accounts-module_store_accounts');

export const emailsStoreKey: StoreInjectionKey<string, IEmailsState, object, IEmailsActions> = Symbol('accounts-module_store_emails');

export const identitiesStoreKey: StoreInjectionKey<string, IIdentitiesState, object, IIdentitiesActions> = Symbol('accounts-module_store_identities');

export const rolesStoreKey: StoreInjectionKey<string, IRolesState, object, IRolesActions> = Symbol('accounts-module_store_roles');

export const sessionStoreKey: StoreInjectionKey<string, ISessionState, object, ISessionActions> = Symbol('accounts-module_store_session');
