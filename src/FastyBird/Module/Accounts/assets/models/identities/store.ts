import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import { v4 as uuid } from 'uuid';

import { IStoresManager, ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.identity.json';
import { accountsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { IIdentitiesStateSemaphore, IdentitiesStoreSetup, IdentityDocument, IdentityState, RoutingKeys } from '../../types';
import { IAccount } from '../accounts/types';

import {
	IIdentitiesActions,
	IIdentitiesAddActionPayload,
	IIdentitiesEditActionPayload,
	IIdentitiesFetchActionPayload,
	IIdentitiesGetActionPayload,
	IIdentitiesRemoveActionPayload,
	IIdentitiesResponseJson,
	IIdentitiesSaveActionPayload,
	IIdentitiesSetActionPayload,
	IIdentitiesSocketDataActionPayload,
	IIdentitiesState,
	IIdentitiesUnsetActionPayload,
	IIdentity,
	IIdentityRecordFactoryPayload,
	IIdentityResponseJson,
	IIdentityResponseModel,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = async (storesManager: IStoresManager, rawData: IIdentityRecordFactoryPayload): Promise<IIdentity> => {
	const accountsStore = storesManager.getStore(accountsStoreKey);

	let account = accountsStore.findById(rawData.accountId);

	if (account === null) {
		if (!(await accountsStore.get({ id: rawData.accountId }))) {
			throw new Error("Account for identity couldn't be loaded from server");
		}

		account = accountsStore.findById(rawData.accountId);

		if (account === null) {
			throw new Error("Account for identity couldn't be loaded from store");
		}
	}

	return {
		id: lodashGet(rawData, 'id', uuid().toString()),
		type: rawData.type,

		draft: lodashGet(rawData, 'draft', false),

		state: lodashGet(rawData, 'state', IdentityState.ACTIVE),

		uid: rawData.uid,
		password: lodashGet(rawData, 'password', undefined) as string | undefined,

		// Relations
		relationshipNames: ['account'],

		account: {
			id: account.id,
			type: account.type,
		},
	} as IIdentity;
};

export const useIdentities = defineStore<'accounts_module_identities', IdentitiesStoreSetup>(
	'accounts_module_identities',
	(): IdentitiesStoreSetup => {
		const storesManager = injectStoresManager();

		const semaphore = ref<IIdentitiesStateSemaphore>({
			fetching: {
				items: [],
				item: [],
			},
			creating: [],
			updating: [],
			deleting: [],
		});

		const firstLoad = ref<IIdentity['id'][]>([]);

		const data = ref<{ [key: IIdentity['id']]: IIdentity }>({});

		const firstLoadFinished = (accountId: IAccount['id']): boolean => {
			return firstLoad.value.includes(accountId);
		};

		const getting = (id: IIdentity['id']): boolean => {
			return semaphore.value.fetching.item.includes(id);
		};

		const fetching = (accountId: IAccount['id'] | null): boolean => {
			return accountId !== null ? semaphore.value.fetching.items.includes(accountId) : semaphore.value.fetching.items.length > 0;
		};

		const findById = (id: IIdentity['id']): IIdentity | null => {
			const identity = Object.values(data.value).find((identity) => identity.id === id);

			return identity ?? null;
		};

		const findForAccount = (accountId: IAccount['id']): IIdentity[] => {
			return Object.values(data.value).filter((identity) => identity.account.id === accountId);
		};

		const set = async (payload: IIdentitiesSetActionPayload): Promise<IIdentity> => {
			const record = await storeRecordFactory(storesManager, payload.data);

			return (data.value[record.id] = record);
		};

		const unset = (payload: IIdentitiesUnsetActionPayload): void => {
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

			throw new Error('You have to provide at least account or identity id');
		};

		const get = async (payload: IIdentitiesGetActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.item.includes(payload.id)) {
				return false;
			}

			semaphore.value.fetching.item.push(payload.id);

			try {
				const identityResponse = await axios.get<IIdentityResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/identities/${payload.id}`
				);

				const identityResponseModel = jsonApiFormatter.deserialize(identityResponse.data) as IIdentityResponseModel;

				data.value[identityResponseModel.id] = await storeRecordFactory(storesManager, {
					...identityResponseModel,
					...{ accountId: identityResponseModel.account.id },
				});
			} catch (e: any) {
				throw new ApiError('accounts-module.identities.get.failed', e, 'Fetching identity failed.');
			} finally {
				semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
			}

			return true;
		};

		const fetch = async (payload: IIdentitiesFetchActionPayload): Promise<boolean> => {
			if (semaphore.value.fetching.items.includes(payload.account.id)) {
				return false;
			}

			semaphore.value.fetching.items.push(payload.account.id);

			try {
				const identitiesResponse = await axios.get<IIdentitiesResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/identities`);

				const identitiesResponseModel = jsonApiFormatter.deserialize(identitiesResponse.data) as IIdentityResponseModel[];

				for (const identity of identitiesResponseModel) {
					data.value[identity.id] = await storeRecordFactory(storesManager, {
						...identity,
						...{ accountId: identity.account.id },
					});
				}

				firstLoad.value.push(payload.account.id);
			} catch (e: any) {
				throw new ApiError('accounts-module.identities.fetch.failed', e, 'Fetching identities failed.');
			} finally {
				semaphore.value.fetching.items = semaphore.value.fetching.items.filter((item) => item !== payload.account.id);
			}

			return true;
		};

		const add = async (payload: IIdentitiesAddActionPayload): Promise<IIdentity> => {
			const newIdentity = await storeRecordFactory(storesManager, {
				...{
					id: payload?.id,
					type: payload?.type,
					draft: payload?.draft,
					accountId: payload.account.id,
				},
				...payload.data,
			});

			semaphore.value.creating.push(newIdentity.id);

			data.value[newIdentity.id] = newIdentity;

			if (newIdentity.draft) {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newIdentity.id);

				return newIdentity;
			} else {
				try {
					const createdIdentity = await axios.post<IIdentityResponseJson>(
						`/${ModulePrefix.ACCOUNTS}/v1/accounts/${payload.account.id}/identities`,
						jsonApiFormatter.serialize({
							stuff: newIdentity,
						})
					);

					const createdIdentityModel = jsonApiFormatter.deserialize(createdIdentity.data) as IIdentityResponseModel;

					data.value[createdIdentityModel.id] = await storeRecordFactory(storesManager, {
						...createdIdentityModel,
						...{ accountId: createdIdentityModel.account.id },
					});

					return data.value[createdIdentityModel.id];
				} catch (e: any) {
					// Entity could not be created on api, we have to remove it from database
					delete data.value[newIdentity.id];

					throw new ApiError('accounts-module.identities.create.failed', e, 'Create new identity failed.');
				} finally {
					semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newIdentity.id);
				}
			}
		};

		const edit = async (payload: IIdentitiesEditActionPayload): Promise<IIdentity> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('accounts-module.identities.update.inProgress');
			}

			if (!Object.keys(data.value).includes(payload.id)) {
				throw new Error('accounts-module.identities.update.failed');
			}

			semaphore.value.updating.push(payload.id);

			// Get record stored in database
			const existingRecord = data.value[payload.id];
			// Update with new values
			const updatedRecord = { ...existingRecord } as IIdentity;

			data.value[payload.id] = updatedRecord;

			if (updatedRecord.draft) {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

				return data.value[payload.id];
			} else {
				try {
					const updatedIdentity = await axios.patch<IIdentityResponseJson>(
						`/${ModulePrefix.ACCOUNTS}/v1/accounts/${updatedRecord.account.id}/identities/${updatedRecord.id}`,
						jsonApiFormatter.serialize({
							stuff: updatedRecord,
						})
					);

					const updatedIdentityModel = jsonApiFormatter.deserialize(updatedIdentity.data) as IIdentityResponseModel;

					data.value[updatedIdentityModel.id] = await storeRecordFactory(storesManager, {
						...updatedIdentityModel,
						...{ accountId: updatedIdentityModel.account.id },
					});

					return data.value[updatedIdentityModel.id];
				} catch (e: any) {
					const accountsStore = storesManager.getStore(accountsStoreKey);

					const account = accountsStore.findById(updatedRecord.account.id);

					if (account !== null) {
						// Updating entity on api failed, we need to refresh entity
						await get({ account, id: payload.id });
					}

					throw new ApiError('accounts-module.identities.update.failed', e, 'Edit identity failed.');
				} finally {
					semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
				}
			}
		};

		const save = async (payload: IIdentitiesSaveActionPayload): Promise<IIdentity> => {
			if (semaphore.value.updating.includes(payload.id)) {
				throw new Error('accounts-module.identities.save.inProgress');
			}

			if (!Object.keys(data.value).includes(payload.id)) {
				throw new Error('accounts-module.identities.save.failed');
			}

			semaphore.value.updating.push(payload.id);

			const recordToSave = data.value[payload.id];

			try {
				const savedIdentity = await axios.post<IIdentityResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToSave.account.id}/identities`,
					jsonApiFormatter.serialize({
						stuff: recordToSave,
					})
				);

				const savedIdentityModel = jsonApiFormatter.deserialize(savedIdentity.data) as IIdentityResponseModel;

				data.value[savedIdentityModel.id] = await storeRecordFactory(storesManager, {
					...savedIdentityModel,
					...{ accountId: savedIdentityModel.account.id },
				});

				return data.value[savedIdentityModel.id];
			} catch (e: any) {
				throw new ApiError('accounts-module.identities.save.failed', e, 'Save draft identity failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		};

		const remove = async (payload: IIdentitiesRemoveActionPayload): Promise<boolean> => {
			if (semaphore.value.deleting.includes(payload.id)) {
				throw new Error('accounts-module.identities.delete.inProgress');
			}

			if (!Object.keys(data.value).includes(payload.id)) {
				throw new Error('accounts-module.identities.delete.failed');
			}

			semaphore.value.deleting.push(payload.id);

			const recordToDelete = data.value[payload.id];

			delete data.value[payload.id];

			if (recordToDelete.draft) {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			} else {
				try {
					await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/accounts/${recordToDelete.account.id}/identities/${recordToDelete.id}`);
				} catch (e: any) {
					const accountsStore = storesManager.getStore(accountsStoreKey);

					const account = accountsStore.findById(recordToDelete.account.id);

					if (account !== null) {
						// Deleting entity on api failed, we need to refresh entity
						await get({ account, id: payload.id });
					}

					throw new ApiError('accounts-module.identities.delete.failed', e, 'Delete identity failed.');
				} finally {
					semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
				}
			}

			return true;
		};

		const socketData = async (payload: IIdentitiesSocketDataActionPayload): Promise<boolean> => {
			if (
				![
					RoutingKeys.IDENTITY_DOCUMENT_REPORTED,
					RoutingKeys.IDENTITY_DOCUMENT_CREATED,
					RoutingKeys.IDENTITY_DOCUMENT_UPDATED,
					RoutingKeys.IDENTITY_DOCUMENT_DELETED,
				].includes(payload.routingKey as RoutingKeys)
			) {
				return false;
			}

			const body: IdentityDocument = JSON.parse(payload.data);

			const isValid = jsonSchemaValidator.compile<IdentityDocument>(exchangeDocumentSchema);

			try {
				if (!isValid(body)) {
					return false;
				}
			} catch {
				return false;
			}

			if (
				!Object.keys(data.value).includes(body.id) &&
				(payload.routingKey === RoutingKeys.IDENTITY_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.IDENTITY_DOCUMENT_DELETED)
			) {
				throw new Error('accounts-module.identities.update.failed');
			}

			if (payload.routingKey === RoutingKeys.IDENTITY_DOCUMENT_DELETED) {
				delete data.value[body.id];
			} else {
				if (payload.routingKey === RoutingKeys.IDENTITY_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
					return true;
				}

				const recordData = await storeRecordFactory(storesManager, {
					id: body.id,
					type: {
						source: body.source,
						entity: 'identity',
					},
					state: body.state,
					uid: body.uid,
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

		return {
			semaphore,
			firstLoad,
			data,
			firstLoadFinished,
			getting,
			fetching,
			findById,
			findForAccount,
			set,
			unset,
			get,
			fetch,
			add,
			edit,
			save,
			remove,
			socketData,
		};
	}
);

export const registerIdentitiesStore = (pinia: Pinia): Store<string, IIdentitiesState, object, IIdentitiesActions> => {
	return useIdentities(pinia);
};
