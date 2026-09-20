import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { format } from 'date-fns';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.account.json';
import { emailsStoreKey, identitiesStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	AccountDocument,
	AccountState,
	AccountsStoreSetup,
	IAccountsActions,
	IAccountsInsertDataActionPayload,
	IAccountsStateSemaphore,
	IEmail,
	IEmailResponseModel,
	IIdentityResponseModel,
	IPlainRelation,
	RoutingKeys,
} from '../../types';

import {
	IAccount,
	IAccountRecordFactoryPayload,
	IAccountResponseJson,
	IAccountResponseModel,
	IAccountsAddActionPayload,
	IAccountsEditActionPayload,
	IAccountsGetActionPayload,
	IAccountsRemoveActionPayload,
	IAccountsResponseJson,
	IAccountsSaveActionPayload,
	IAccountsSetActionPayload,
	IAccountsSocketDataActionPayload,
	IAccountsState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (storesManager: IStoresManager, rawData: IAccountRecordFactoryPayload): IAccount => {
	const record: IAccount = {
		id: lodashGet(rawData, 'id', uuid().toString()),
		type: rawData.type,

		draft: lodashGet(rawData, 'draft', false),

		details: {
			firstName: rawData.details.firstName,
			lastName: rawData.details.lastName,
			middleName: lodashGet(rawData, 'details.middleName', null),
		},

		language: lodashGet(rawData, 'language', 'en'),

		weekStart: lodashGet(rawData, 'weekStart', 1),

		dateTime: {
			timezone: lodashGet(rawData, 'dateTime.timezone', 'Europe/Prague'),
			dateFormat: lodashGet(rawData, 'dateTime.dateFormat', 'dd.MM.yyyy'),
			timeFormat: lodashGet(rawData, 'dateTime.timeFormat', 'HH:mm'),
		},

		state: lodashGet(rawData, 'state', AccountState.NOT_ACTIVATED),

		lastVisit: null,
		registered: format(new Date(), "yyyy-MM-dd'T'HH:mm:ssXXXXX"),

		relationshipNames: ['emails', 'identities', 'roles'],

		emails: [],
		identities: [],
		roles: [],

		get name(): string {
			return `${this.details.firstName} ${this.details.lastName}`;
		},

		get email(): IEmail | null {
			const emailsStore = storesManager.getStore(emailsStoreKey);

			const defaultEmail = emailsStore.findForAccount(this.id).find((email) => email.isDefault);

			return defaultEmail ?? null;
		},
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'emails' || relationName === 'identities' || relationName === 'roles') {
			lodashGet(rawData, relationName, []).forEach((relation: any): void => {
				if (lodashGet(relation, 'id', null) !== null && lodashGet(relation, 'type', null) !== null) {
					(record[relationName] as IPlainRelation[]).push({
						id: lodashGet(relation, 'id', null),
						type: lodashGet(relation, 'type', null),
					});
				}
			});
		}
	});

	return record;
};

const addEmailsRelations = async (
	storesManager: IStoresManager,
	account: IAccount,
	emails: (IEmailResponseModel | IPlainRelation)[]
): Promise<void> => {
	const emailsStore = storesManager.getStore(emailsStoreKey);

	for (const email of emails) {
		if ('address' in email) {
			await emailsStore.set({
				data: {
					...email,
					...{
						accountId: account.id,
					},
				},
			});
		}
	}
};

const addIdentitiesRelations = async (
	storesManager: IStoresManager,
	account: IAccount,
	identities: (IIdentityResponseModel | IPlainRelation)[]
): Promise<void> => {
	const identitiesStore = storesManager.getStore(identitiesStoreKey);

	for (const identity of identities) {
		if ('uid' in identity) {
			await identitiesStore.set({
				data: {
					...identity,
					...{
						accountId: account.id,
					},
				},
			});
		}
	}
};

export const useAccounts = defineStore<'accounts_module_accounts', AccountsStoreSetup>('accounts_module_accounts', (): AccountsStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IAccountsStateSemaphore>({
		fetching: {
			items: false,
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<boolean>(false);

	const data = ref<{ [key: IAccount['id']]: IAccount }>({});

	const findById = (id: IAccount['id']): IAccount | null => (id in data.value ? data.value[id] : null);

	const set = async (payload: IAccountsSetActionPayload): Promise<IAccount> => {
		const record = storeRecordFactory(storesManager, payload.data);

		if ('emails' in payload.data && Array.isArray(payload.data.emails)) {
			await addEmailsRelations(storesManager, record, payload.data.emails);
		}

		if ('identities' in payload.data && Array.isArray(payload.data.identities)) {
			await addIdentitiesRelations(storesManager, record, payload.data.identities);
		}

		return (data.value[record.id] = record);
	};

	const get = async (payload: IAccountsGetActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.item.includes(payload.id)) {
			return false;
		}

		semaphore.value.fetching.item.push(payload.id);

		try {
			const accountResponse = await axios.get<IAccountResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.id}`);

			const accountResponseModel = jsonApiFormatter.deserialize(accountResponse.data) as IAccountResponseModel;

			data.value[accountResponseModel.id] = storeRecordFactory(storesManager, accountResponseModel);

			await addEmailsRelations(storesManager, data.value[accountResponseModel.id], accountResponseModel.emails);
			await addIdentitiesRelations(storesManager, data.value[accountResponseModel.id], accountResponseModel.identities);
		} catch (e: any) {
			throw new ApiError('accounts-module.accounts.get.failed', e, 'Fetching account failed.');
		} finally {
			semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
		}

		const promises: Promise<boolean>[] = [];

		const emailsStore = storesManager.getStore(emailsStoreKey);
		promises.push(emailsStore.fetch({ account: data.value[payload.id] }));

		const identitiesStore = storesManager.getStore(identitiesStoreKey);
		promises.push(identitiesStore.fetch({ account: data.value[payload.id] }));

		Promise.all(promises).catch((e: any): void => {
			throw new ApiError('accounts-module.accounts.get.failed', e, 'Fetching account failed.');
		});

		return true;
	};

	const fetch = async (): Promise<boolean> => {
		if (semaphore.value.fetching.items) {
			return false;
		}

		semaphore.value.fetching.items = true;

		try {
			const accountsResponse = await axios.get<IAccountsResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts`);

			const accountsResponseModel = jsonApiFormatter.deserialize(accountsResponse.data) as IAccountResponseModel[];

			accountsResponseModel.forEach((account) => {
				data.value[account.id] = storeRecordFactory(storesManager, account);

				addEmailsRelations(storesManager, data.value[account.id], account.emails);
				addIdentitiesRelations(storesManager, data.value[account.id], account.identities);
			});

			firstLoad.value = true;
		} catch (e: any) {
			throw new ApiError('accounts-module.accounts.fetch.failed', e, 'Fetching accounts failed.');
		} finally {
			semaphore.value.fetching.items = false;
		}

		const promises: Promise<boolean>[] = [];

		const emailsStore = storesManager.getStore(emailsStoreKey);
		const identitiesStore = storesManager.getStore(identitiesStoreKey);

		for (const account of Object.values(data.value ?? {})) {
			promises.push(emailsStore.fetch({ account }));
			promises.push(identitiesStore.fetch({ account }));
		}

		Promise.all(promises).catch((e: any): void => {
			throw new ApiError('accounts-module.accounts.fetch.failed', e, 'Fetching accounts failed.');
		});

		return true;
	};

	const add = async (payload: IAccountsAddActionPayload): Promise<IAccount> => {
		const newAccount = storeRecordFactory(storesManager, {
			...{ id: payload?.id, type: payload?.type, draft: payload?.draft },
			...payload.data,
		});

		semaphore.value.creating.push(newAccount.id);

		data.value[newAccount.id] = newAccount;

		if (newAccount.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newAccount.id);

			return newAccount;
		} else {
			try {
				const createdAccount = await axios.post<IAccountResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts`,
					jsonApiFormatter.serialize({
						stuff: newAccount,
					})
				);

				const createdAccountModel = jsonApiFormatter.deserialize(createdAccount.data) as IAccountResponseModel;

				data.value[createdAccountModel.id] = storeRecordFactory(storesManager, createdAccountModel);
			} catch (e: any) {
				// Record could not be created on api, we have to remove it from database
				delete data.value[newAccount.id];

				throw new ApiError('accounts-module.accounts.create.failed', e, 'Create new account failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newAccount.id);
			}

			const promises: Promise<boolean>[] = [];

			const emailsStore = storesManager.getStore(emailsStoreKey);
			promises.push(emailsStore.fetch({ account: data.value[newAccount.id] }));

			const identitiesStore = storesManager.getStore(identitiesStoreKey);
			promises.push(identitiesStore.fetch({ account: data.value[newAccount.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('accounts-module.accounts.create.failed', e, 'Create new account failed.');
			});

			return data.value[newAccount.id];
		}
	};

	const edit = async (payload: IAccountsEditActionPayload): Promise<IAccount> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.accounts.update.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.accounts.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IAccount;

		data.value[payload.id] = updatedRecord;

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const updatedAccount = await axios.patch<IAccountResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.id}`,
					jsonApiFormatter.serialize({
						stuff: updatedRecord,
					})
				);

				const updatedAccountModel = jsonApiFormatter.deserialize(updatedAccount.data) as IAccountResponseModel;

				data.value[updatedAccountModel.id] = storeRecordFactory(storesManager, updatedAccountModel);
			} catch (e: any) {
				// Updating record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('accounts-module.accounts.update.failed', e, 'Edit account failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}

			const promises: Promise<boolean>[] = [];

			const emailsStore = storesManager.getStore(emailsStoreKey);
			promises.push(emailsStore.fetch({ account: data.value[payload.id] }));

			const identitiesStore = storesManager.getStore(identitiesStoreKey);
			promises.push(identitiesStore.fetch({ account: data.value[payload.id] }));

			Promise.all(promises).catch((e: any): void => {
				throw new ApiError('accounts-module.accounts.update.failed', e, 'Edit account failed.');
			});

			return data.value[payload.id];
		}
	};

	const save = async (payload: IAccountsSaveActionPayload): Promise<IAccount> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.accounts.save.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.accounts.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const savedAccount = await axios.post<IAccountResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/accounts`,
				jsonApiFormatter.serialize({
					stuff: recordToSave,
				})
			);

			const savedAccountModel = jsonApiFormatter.deserialize(savedAccount.data) as IAccountResponseModel;

			data.value[savedAccountModel.id] = storeRecordFactory(storesManager, savedAccountModel);
		} catch (e: any) {
			throw new ApiError('accounts-module.accounts.save.failed', e, 'Save draft account failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}

		const promises: Promise<boolean>[] = [];

		const emailsStore = storesManager.getStore(emailsStoreKey);
		promises.push(emailsStore.fetch({ account: data.value[payload.id] }));

		const identitiesStore = storesManager.getStore(identitiesStoreKey);
		promises.push(identitiesStore.fetch({ account: data.value[payload.id] }));

		Promise.all(promises).catch((e: any): void => {
			throw new ApiError('accounts-module.accounts.save.failed', e, 'Save draft account failed.');
		});

		return data.value[payload.id];
	};

	const remove = async (payload: IAccountsRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('accounts-module.accounts.delete.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			return true;
		}

		const emailsStore = storesManager.getStore(emailsStoreKey);
		const identitiesStore = storesManager.getStore(identitiesStoreKey);

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);

			emailsStore.unset({ account: recordToDelete });
			identitiesStore.unset({ account: recordToDelete });
		} else {
			try {
				await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/accounts${payload.id}`);

				emailsStore.unset({ account: recordToDelete });
				identitiesStore.unset({ account: recordToDelete });
			} catch (e: any) {
				// Deleting record on api failed, we need to refresh record
				await get({ id: payload.id });

				throw new ApiError('accounts-module.accounts.delete.failed', e, 'Delete account failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const socketData = async (payload: IAccountsSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.ACCOUNT_DOCUMENT_REPORTED,
				RoutingKeys.ACCOUNT_DOCUMENT_CREATED,
				RoutingKeys.ACCOUNT_DOCUMENT_UPDATED,
				RoutingKeys.ACCOUNT_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: AccountDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<AccountDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (
			!Object.keys(data.value).includes(body.id) &&
			(payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_DELETED)
		) {
			throw new Error('accounts-module.accounts.update.failed');
		}

		if (payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_DELETED) {
			const recordToDelete = data.value[body.id];

			delete data.value[body.id];

			const emailsStore = storesManager.getStore(emailsStoreKey);
			const identitiesStore = storesManager.getStore(identitiesStoreKey);

			emailsStore.unset({ account: recordToDelete });
			identitiesStore.unset({ account: recordToDelete });
		} else {
			if (payload.routingKey === RoutingKeys.ACCOUNT_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			const recordData = storeRecordFactory(storesManager, {
				id: body.id,
				type: {
					source: body.source,
					entity: 'account',
				},
				details: {
					firstName: body.first_name,
					lastName: body.last_name,
					middleName: body.middle_name,
				},
				language: body.language,
				registered: body.registered,
				lastVisit: body.last_visit,
				state: body.state,
			});

			if (body.id in data.value) {
				data.value[body.id] = { ...data.value[body.id], ...recordData };
			} else {
				data.value[body.id] = recordData;
			}
		}

		return true;
	};

	const insertData = async (payload: IAccountsInsertDataActionPayload): Promise<boolean> => {
		data.value = data.value ?? {};

		let documents: AccountDocument[];

		if (Array.isArray(payload.data)) {
			documents = payload.data;
		} else {
			documents = [payload.data];
		}

		for (const doc of documents) {
			const isValid = jsonSchemaValidator.compile<AccountDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(doc)) {
					return false;
				}
			} catch {
				return false;
			}

			const record = storeRecordFactory(storesManager, {
				...data.value[doc.id],
				...{
					id: doc.id,
					details: {
						firstName: doc.first_name,
						lastName: doc.last_name,
						middleName: doc.middle_name,
					},
					language: doc.language,
					state: doc.state,
					registered: doc.registered,
					lastVisit: doc.last_visit,
				},
			});

			if (documents.length === 1) {
				data.value[doc.id] = record;
			}
		}

		return true;
	};

	return { semaphore, firstLoad, data, findById, set, get, fetch, add, edit, save, remove, socketData, insertData };
});

export const registerAccountsStore = (pinia: Pinia): Store<string, IAccountsState, object, IAccountsActions> => {
	return useAccounts(pinia);
};
