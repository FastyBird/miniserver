import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import axios from 'axios';
import { Jsona } from 'jsona';

import { ModulePrefix, ModuleSource, injectStoresManager } from '@fastybird/miniserver-core';

import { accountsStoreKey, emailsStoreKey, identitiesStoreKey, sessionStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import {
	AccountStoreSetup,
	IAccount,
	IAccountActions,
	IAccountResponseJson,
	IAccountResponseModel,
	IAccountStateSemaphore,
	IEmail,
	IEmailResponseJson,
	IEmailResponseModel,
	IIdentityResponseJson,
} from '../../types';

import {
	IAccountAddEmailActionPayload,
	IAccountEditActionPayload,
	IAccountEditEmailActionPayload,
	IAccountEditIdentityActionPayload,
	IAccountRegisterActionPayload,
	IAccountRequestResetActionPayload,
	IAccountState,
} from './types';

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

export const useAccount = defineStore<'accounts_module_account', AccountStoreSetup>('accounts_module_account', (): AccountStoreSetup => {
	const storesManager = injectStoresManager();

	const semaphore = ref<IAccountStateSemaphore>({
		creating: false,
		updating: false,
	});

	const loaded = ref<boolean>(false);

	const emails = (): IEmail[] => {
		const sessionStore = storesManager.getStore(sessionStoreKey);

		if (sessionStore.account() === null) {
			return [];
		}

		const emailsStore = storesManager.getStore(emailsStoreKey);

		return emailsStore.findForAccount(sessionStore.account()!.id);
	};

	const edit = async (payload: IAccountEditActionPayload): Promise<boolean> => {
		if (semaphore.value.updating) {
			throw new Error('accounts-module.account.update.inProgress');
		}

		const sessionStore = storesManager.getStore(sessionStoreKey);

		const account = sessionStore.account();

		if (account === null) {
			throw new Error('accounts-module.account.update.failed');
		}

		const accountsStore = storesManager.getStore(accountsStoreKey);

		semaphore.value.updating = true;

		// Update with new values
		const updatedRecord = { ...account, ...payload.data } as IAccount;

		try {
			const updatedAccount = await axios.patch<IAccountResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/me?include=emails,identities,roles`,
				jsonApiFormatter.serialize({
					stuff: updatedRecord,
				})
			);

			const updatedAccountModel = jsonApiFormatter.deserialize(updatedAccount.data) as IAccountResponseModel;

			await accountsStore.set({ data: updatedAccountModel });
		} catch (e: any) {
			// Updating record on api failed, we need to refresh record
			await accountsStore.get({ id: account.id });

			throw new ApiError('accounts-module.account.update.failed', e, 'Edit account failed.');
		} finally {
			semaphore.value.updating = false;
		}

		return true;
	};

	const addEmail = async (payload: IAccountAddEmailActionPayload): Promise<IEmail> => {
		if (semaphore.value.creating) {
			throw new Error('accounts-module.account.create.inProgress');
		}

		const sessionStore = storesManager.getStore(sessionStoreKey);

		const account = sessionStore.account();

		if (account === null) {
			throw new Error('accounts-module.account.update.failed');
		}

		semaphore.value.creating = true;

		const emailsStore = storesManager.getStore(emailsStoreKey);

		const newEmail = await emailsStore.set({
			data: {
				...payload.data,
				...{
					type: {
						source: ModuleSource.ACCOUNTS,
						entity: 'email',
					},
					accountId: account.id,
				},
			},
		});

		if (newEmail.draft) {
			semaphore.value.creating = false;

			return newEmail;
		} else {
			try {
				const createdEmail = await axios.post<IEmailResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/me/emails`,
					jsonApiFormatter.serialize({
						stuff: newEmail,
					})
				);

				const createdEmailModel = jsonApiFormatter.deserialize(createdEmail.data) as IEmailResponseModel;

				return await emailsStore.set({
					data: {
						...createdEmailModel,
						...{
							accountId: createdEmailModel.account.id,
						},
					},
				});
			} catch (e: any) {
				// Entity could not be created on api, we have to remove it from database
				emailsStore.unset({
					id: newEmail.id,
				});

				throw new ApiError('accounts-module.account.create.failed', e, 'Create new email failed.');
			} finally {
				semaphore.value.creating = false;
			}
		}
	};

	const editEmail = async (payload: IAccountEditEmailActionPayload): Promise<IEmail> => {
		if (semaphore.value.updating) {
			throw new Error('accounts-module.account.update.inProgress');
		}

		const emailsStore = storesManager.getStore(emailsStoreKey);

		const email = emailsStore.findByAddress(payload.id);

		if (email === null) {
			throw new Error('accounts-module.account.update.failed');
		}

		semaphore.value.updating = true;

		// Update with new values
		const updatedRecord = { ...email, ...payload.data } as IEmail;

		if (updatedRecord.draft) {
			semaphore.value.updating = false;

			return await emailsStore.set({
				data: {
					...updatedRecord,
					...{
						accountId: email.account.id,
					},
				},
			});
		} else {
			try {
				const updatedEmail = await axios.patch<IEmailResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/me/emails/${updatedRecord.id}`,
					jsonApiFormatter.serialize({
						stuff: updatedRecord,
					})
				);

				const updatedEmailModel = jsonApiFormatter.deserialize(updatedEmail.data) as IEmailResponseModel;

				return await emailsStore.set({
					data: {
						...updatedEmailModel,
						...{
							accountId: email.account.id,
						},
					},
				});
			} catch (e: any) {
				const accountsStore = storesManager.getStore(accountsStoreKey);

				const account = accountsStore.findById(updatedRecord.account.id);

				if (account !== null) {
					// Updating entity on api failed, we need to refresh entity
					await emailsStore.get({ account, id: payload.id });
				}

				throw new ApiError('accounts-module.account.update.failed', e, 'Edit email failed.');
			} finally {
				semaphore.value.updating = false;
			}
		}
	};

	const editIdentity = async (payload: IAccountEditIdentityActionPayload): Promise<boolean> => {
		if (semaphore.value.updating) {
			throw new Error('accounts-module.account.update.inProgress');
		}

		const identitiesStore = storesManager.getStore(identitiesStoreKey);

		const identity = identitiesStore.findById(payload.id);

		if (identity === null) {
			throw new Error('accounts-module.account.update.failed');
		}

		semaphore.value.updating = true;

		try {
			await axios.patch<IIdentityResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/me/identities/${identity.id}`,
				jsonApiFormatter.serialize({
					stuff: {
						...identity,
						...{
							password: {
								current: payload.data.password.current,
								new: payload.data.password.new,
							},
						},
					},
				})
			);
		} catch (e: any) {
			const accountsStore = storesManager.getStore(accountsStoreKey);

			const account = accountsStore.findById(identity.account.id);

			if (account !== null) {
				// Updating entity on api failed, we need to refresh entity
				await identitiesStore.get({ account, id: payload.id });
			}

			throw new ApiError('accounts-module.account.update.failed', e, 'Edit identity failed.');
		} finally {
			semaphore.value.updating = false;
		}

		return true;
	};

	const requestReset = async (payload: IAccountRequestResetActionPayload): Promise<boolean> => {
		try {
			const resetResponse = await axios.post(
				`/${ModulePrefix.ACCOUNTS}/v1/reset-identity`,
				jsonApiFormatter.serialize({
					stuff: {
						type: `${ModuleSource.ACCOUNTS}/identity`,

						uid: payload.uid,
					},
				})
			);

			return resetResponse.status >= 200 && resetResponse.status < 300;
		} catch (e: any) {
			throw new ApiError('accounts-module.account.requestReset.failed', e, 'Request identity reset failed.');
		}
	};

	const register = async (payload: IAccountRegisterActionPayload): Promise<boolean> => {
		// TODO: Implement

		try {
			await axios.post<IAccountResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/register`,
				jsonApiFormatter.serialize({
					stuff: {
						email: payload.emailAddress,
					},
				})
			);
		} catch (e: any) {
			throw new ApiError('accounts-module.account.register.failed', e, 'Register account failed.');
		}

		return true;
	};

	return { semaphore, loaded, emails, edit, addEmail, editEmail, editIdentity, requestReset, register };
});

export const registerAccountStore = (pinia: Pinia): Store<string, IAccountState, object, IAccountActions> => {
	return useAccount(pinia);
};
