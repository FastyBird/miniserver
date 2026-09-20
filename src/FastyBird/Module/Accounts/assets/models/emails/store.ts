import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, ModuleSource, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.email.json';
import { accountsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { EmailDocument, EmailsStoreSetup, IEmailsStateSemaphore, RoutingKeys } from '../../types';
import { IAccount } from '../accounts/types';

import {
	IEmail,
	IEmailRecordFactoryPayload,
	IEmailResponseJson,
	IEmailResponseModel,
	IEmailsActions,
	IEmailsAddActionPayload,
	IEmailsEditActionPayload,
	IEmailsFetchActionPayload,
	IEmailsGetActionPayload,
	IEmailsInsertDataActionPayload,
	IEmailsRemoveActionPayload,
	IEmailsResponseJson,
	IEmailsSaveActionPayload,
	IEmailsSetActionPayload,
	IEmailsSocketDataActionPayload,
	IEmailsState,
	IEmailsUnsetActionPayload,
	IEmailsValidateActionPayload,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, rawData: IEmailRecordFactoryPayload): Promise<IEmail> => {
	const accountsStore = storesManager.getStore(accountsStoreKey);

	let account = accountsStore.findById(rawData.accountId);

	if (account === null) {
		if (!(await accountsStore.get({ id: rawData.accountId }))) {
			throw new Error("Account for email couldn't be loaded from server");
		}

		account = accountsStore.findById(rawData.accountId);

		if (account === null) {
			throw new Error("Account for email couldn't be loaded from store");
		}
	}

	return {
		id: lodashGet(rawData, 'id', uuid().toString()),
		type: rawData.type,

		draft: lodashGet(rawData, 'draft', false),

		address: rawData.address,
		default: lodashGet(rawData, 'default', false),
		private: lodashGet(rawData, 'private', false),
		verified: lodashGet(rawData, 'verified', false),

		// Relations
		relationshipNames: ['account'],

		account: {
			id: account.id,
			type: account.type,
		},

		// Entity transformers
		get isDefault(): boolean {
			return this.default;
		},

		get isPrivate(): boolean {
			return this.private;
		},

		get isVerified(): boolean {
			return this.verified;
		},
	} as IEmail;
};

export const useEmails = defineStore<'accounts_module_emails', EmailsStoreSetup>('accounts_module_emails', (): EmailsStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IEmailsStateSemaphore>({
		fetching: {
			items: [],
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<IEmail['id'][]>([]);

	const data = ref<{ [key: IEmail['id']]: IEmail }>({});

	const firstLoadFinished = (accountId: IAccount['id']): boolean => firstLoad.value.includes(accountId);

	const getting = (id: IEmail['id']): boolean => semaphore.value.fetching.item.includes(id);

	const fetching = (accountId: IAccount['id'] | null): boolean =>
		accountId !== null ? semaphore.value.fetching.items.includes(accountId) : semaphore.value.fetching.items.length > 0;

	const findById = (id: IEmail['id']): IEmail | null => {
		const email = Object.values(data.value).find((email) => email.id === id);

		return email ?? null;
	};

	const findByAddress = (address: IEmail['address']): IEmail | null => {
		const email = Object.values(data.value).find((email) => email.address.toLowerCase() === address.toLowerCase());

		return email ?? null;
	};

	const findForAccount = (accountId: IAccount['id']): IEmail[] => {
		return Object.values(data.value).filter((email) => email.account.id === accountId);
	};

	const set = async (payload: IEmailsSetActionPayload): Promise<IEmail> => {
		const record = await storeRecordFactory(storesManager, payload.data);

		return (data.value[record.id] = record);
	};

	const unset = (payload: IEmailsUnsetActionPayload): void => {
		if (payload.account !== undefined) {
			Object.keys(data.value).forEach((id) => {
				if (id in data.value && data.value[id].account.id === payload.account?.id) {
					delete data.value[id];
				}
			});

			return;
		} else if (payload.id !== undefined) {
			if (payload.id in data.value) {
				delete data.value[payload.id];
			}

			return;
		}

		throw new Error('You have to provide at least account or email id');
	};

	const get = async (payload: IEmailsGetActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.item.includes(payload.id)) {
			return false;
		}

		semaphore.value.fetching.item.push(payload.id);

		try {
			const emailResponse = await axios.get<IEmailResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails/${payload.id}`);

			const emailResponseModel = jsonApiFormatter.deserialize(emailResponse.data) as IEmailResponseModel;

			data.value[emailResponseModel.id] = await storeRecordFactory(storesManager, {
				...emailResponseModel,
				...{ accountId: emailResponseModel.account.id },
			});
		} catch (e: any) {
			throw new ApiError('accounts-module.emails.get.failed', e, 'Fetching email failed.');
		} finally {
			semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
		}

		return true;
	};

	const fetch = async (payload: IEmailsFetchActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.items.includes(payload.account.id)) {
			return false;
		}

		semaphore.value.fetching.items.push(payload.account.id);

		try {
			const emailsResponse = await axios.get<IEmailsResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails`);

			const emailsResponseModel = jsonApiFormatter.deserialize(emailsResponse.data) as IEmailResponseModel[];

			for (const email of emailsResponseModel) {
				data.value[email.id] = await storeRecordFactory(storesManager, {
					...email,
					...{ accountId: email.account.id },
				});
			}

			firstLoad.value.push(payload.account.id);
		} catch (e: any) {
			throw new ApiError('accounts-module.emails.fetch.failed', e, 'Fetching emails failed.');
		} finally {
			semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.account.id);
		}

		return true;
	};

	const add = async (payload: IEmailsAddActionPayload): Promise<IEmail> => {
		const newEmail = await storeRecordFactory(storesManager, {
			...{
				id: payload?.id,
				type: payload?.type,
				draft: payload?.draft,
				accountId: payload.account.id,
			},
			...payload.data,
		});

		semaphore.value.creating.push(newEmail.id);

		data.value[newEmail.id] = newEmail;

		if (newEmail.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newEmail.id);

			return newEmail;
		} else {
			try {
				const createdEmail = await axios.post<IEmailResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/emails`,
					jsonApiFormatter.serialize({
						stuff: newEmail,
					})
				);

				const createdEmailModel = jsonApiFormatter.deserialize(createdEmail.data) as IEmailResponseModel;

				data.value[createdEmailModel.id] = await storeRecordFactory(storesManager, {
					...createdEmailModel,
					...{ accountId: createdEmailModel.account.id },
				});

				return data.value[createdEmailModel.id];
			} catch (e: any) {
				// Entity could not be created on api, we have to remove it from database
				delete data.value[newEmail.id];

				throw new ApiError('accounts-module.emails.create.failed', e, 'Create new email failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newEmail.id);
			}
		}
	};

	const edit = async (payload: IEmailsEditActionPayload): Promise<IEmail> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.emails.update.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.emails.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IEmail;

		data.value[payload.id] = updatedRecord;

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const updatedEmail = await axios.patch<IEmailResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${updatedRecord.account.id}/emails/${updatedRecord.id}`,
					jsonApiFormatter.serialize({
						stuff: updatedRecord,
					})
				);

				const updatedEmailModel = jsonApiFormatter.deserialize(updatedEmail.data) as IEmailResponseModel;

				data.value[updatedEmailModel.id] = await storeRecordFactory(storesManager, {
					...updatedEmailModel,
					...{ accountId: updatedEmailModel.account.id },
				});

				return data.value[updatedEmailModel.id];
			} catch (e: any) {
				const accountsStore = storesManager.getStore(accountsStoreKey);

				const account = accountsStore.findById(updatedRecord.account.id);

				if (account !== null) {
					// Updating entity on api failed, we need to refresh entity
					await get({ account, id: payload.id });
				}

				throw new ApiError('accounts-module.emails.update.failed', e, 'Edit email failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		}
	};

	const save = async (payload: IEmailsSaveActionPayload): Promise<IEmail> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.emails.save.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.emails.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const savedEmail = await axios.post<IEmailResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToSave.account.id}/emails`,
				jsonApiFormatter.serialize({
					stuff: recordToSave,
				})
			);

			const savedEmailModel = jsonApiFormatter.deserialize(savedEmail.data) as IEmailResponseModel;

			data.value[savedEmailModel.id] = await storeRecordFactory(storesManager, {
				...savedEmailModel,
				...{ accountId: savedEmailModel.account.id },
			});

			return data.value[savedEmailModel.id];
		} catch (e: any) {
			throw new ApiError('accounts-module.emails.save.failed', e, 'Save draft email failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}
	};

	const remove = async (payload: IEmailsRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('accounts-module.emails.delete.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.emails.delete.failed');
		}

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
		} else {
			try {
				await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToDelete.account.id}/emails/${recordToDelete.id}`);
			} catch (e: any) {
				const accountsStore = storesManager.getStore(accountsStoreKey);

				const account = accountsStore.findById(recordToDelete.account.id);

				if (account !== null) {
					// Deleting entity on api failed, we need to refresh entity
					await get({ account, id: payload.id });
				}

				throw new ApiError('accounts-module.emails.delete.failed', e, 'Delete email failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const validate = async (payload: IEmailsValidateActionPayload): Promise<any> => {
		try {
			const validateResponse = await axios.post(
				`/${ModulePrefix.ACCOUNTS}/v1/validate-email`,
				jsonApiFormatter.serialize({
					stuff: {
						type: `${ModuleSource.ACCOUNTS}/email`,

						address: payload.address,
					},
				})
			);

			return validateResponse.status >= 200 && validateResponse.status < 300;
		} catch (e: any) {
			throw new ApiError('accounts-module.emails.validate.failed', e, 'Validate email address failed.');
		}
	};

	const socketData = async (payload: IEmailsSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.EMAIL_DOCUMENT_REPORTED,
				RoutingKeys.EMAIL_DOCUMENT_CREATED,
				RoutingKeys.EMAIL_DOCUMENT_UPDATED,
				RoutingKeys.EMAIL_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: EmailDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<EmailDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (
			!Object.keys(data.value).includes(body.id) &&
			(payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_DELETED)
		) {
			throw new Error('accounts-module.emails.update.failed');
		}

		if (payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_DELETED) {
			delete data.value[body.id];
		} else {
			if (payload.routingKey === RoutingKeys.EMAIL_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			const recordData = await storeRecordFactory(storesManager, {
				id: body.id,
				type: {
					source: body.source,
					entity: 'email',
				},
				address: body.address,
				default: body.default,
				verified: body.verified,
				private: body.private,
				accountId: body.account,
			});

			if (body.id in data.value) {
				data.value[body.id] = { ...data.value[body.id], ...recordData };
			} else {
				data.value[body.id] = recordData;
			}
		}

		return true;
	};

	const insertData = async (payload: IEmailsInsertDataActionPayload): Promise<boolean> => {
		data.value = data.value ?? {};

		let documents: EmailDocument[];

		if (Array.isArray(payload.data)) {
			documents = payload.data;
		} else {
			documents = [payload.data];
		}

		const accountIds = [];

		for (const doc of documents) {
			const isValid = jsonSchemaValidator.compile<EmailDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(doc)) {
					return false;
				}
			} catch {
				return false;
			}

			const record = await storeRecordFactory(storesManager, {
				...data.value[doc.id],
				...{
					id: doc.id,
					address: doc.address,
					default: doc.default,
					public: doc.public,
					private: doc.private,
					verified: doc.verified,
					accountId: doc.account,
				},
			});

			if (documents.length === 1) {
				data.value[doc.id] = record;
			}

			accountIds.push(doc.account);
		}

		if (documents.length > 1) {
			const uniqueAccountIds = [...new Set(accountIds)];

			if (uniqueAccountIds.length > 1) {
				firstLoad.value = [...new Set(firstLoad.value)];
			}

			for (const deviceId of uniqueAccountIds) {
				firstLoad.value.push(deviceId);
				firstLoad.value = [...new Set(firstLoad.value)];
			}
		}

		return true;
	};

	return {
		semaphore,
		firstLoad,
		data,
		firstLoadFinished,
		getting,
		fetching,
		findById,
		findByAddress,
		findForAccount,
		set,
		unset,
		get,
		fetch,
		add,
		edit,
		save,
		remove,
		validate,
		socketData,
		insertData,
	};
});

export const registerEmailsStore = (pinia: Pinia): Store<string, IEmailsState, object, IEmailsActions> => {
	return useEmails(pinia);
};
