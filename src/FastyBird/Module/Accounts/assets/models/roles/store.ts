import { ref } from 'vue';

import { Pinia, Store, defineStore } from 'pinia';

import addFormats from 'ajv-formats';
import Ajv from 'ajv/dist/2020';
import axios from 'axios';
import { Jsona } from 'jsona';
import lodashGet from 'lodash.get';
import { v4 as uuid } from 'uuid';

import { ModulePrefix } from '@fastybird/miniserver-core';

import exchangeDocumentSchema from '../../../resources/schemas/document.role.json';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { IRolesStateSemaphore, RoleDocument, RolesStoreSetup, RoutingKeys } from '../../types';
import { IPlainRelation } from '../types';

import {
	IRole,
	IRoleRecordFactoryPayload,
	IRoleResponseJson,
	IRoleResponseModel,
	IRolesActions,
	IRolesAddActionPayload,
	IRolesEditActionPayload,
	IRolesGetActionPayload,
	IRolesRemoveActionPayload,
	IRolesResponseJson,
	IRolesSaveActionPayload,
	IRolesSocketDataActionPayload,
	IRolesState,
} from './types';

const jsonSchemaValidator = new Ajv();
addFormats(jsonSchemaValidator);

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const storeRecordFactory = (rawData: IRoleRecordFactoryPayload): IRole => {
	const record: IRole = {
		id: lodashGet(rawData, 'id', uuid().toString()),
		type: rawData.type,

		draft: lodashGet(rawData, 'draft', false),

		name: rawData.name,
		description: lodashGet(rawData, 'description', null),

		anonymous: lodashGet(rawData, 'anonymous', false),
		authenticated: lodashGet(rawData, 'authenticated', false),
		administrator: lodashGet(rawData, 'administrator', false),

		// Relations
		relationshipNames: ['accounts'],

		accounts: [],
	};

	record.relationshipNames.forEach((relationName) => {
		if (relationName === 'accounts') {
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

export const useRoles = defineStore<'accounts_module_roles', RolesStoreSetup>('accounts_module_roles', (): RolesStoreSetup => {
	const semaphore = ref<IRolesStateSemaphore>({
		fetching: {
			items: false,
			item: [],
		},
		creating: [],
		updating: [],
		deleting: [],
	});

	const firstLoad = ref<boolean>(false);

	const data = ref<{ [key: IRole['id']]: IRole }>({});

	const firstLoadFinished = (): boolean => {
		return firstLoad.value;
	};

	const getting = (id: IRole['id']): boolean => {
		return semaphore.value.fetching.item.includes(id);
	};

	const fetching = (): boolean => {
		return semaphore.value.fetching.items;
	};

	const get = async (payload: IRolesGetActionPayload): Promise<boolean> => {
		if (semaphore.value.fetching.item.includes(payload.id)) {
			return false;
		}

		semaphore.value.fetching.item.push(payload.id);

		try {
			const roleResponse = await axios.get<IRoleResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/roles/${payload.id}`);

			const roleResponseModel = jsonApiFormatter.deserialize(roleResponse.data) as IRoleResponseModel;

			data.value[roleResponseModel.id] = storeRecordFactory(roleResponseModel);
		} catch (e: any) {
			throw new ApiError('accounts-module.roles.get.failed', e, 'Fetching role failed.');
		} finally {
			semaphore.value.fetching.item = semaphore.value.fetching.item.filter((item) => item !== payload.id);
		}

		return true;
	};

	const fetch = async (): Promise<boolean> => {
		if (semaphore.value.fetching.items) {
			return false;
		}

		semaphore.value.fetching.items = true;

		try {
			const rolesResponse = await axios.get<IRolesResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/roles`);

			const rolesResponseModel = jsonApiFormatter.deserialize(rolesResponse.data) as IRoleResponseModel[];

			rolesResponseModel.forEach((role) => (data.value[role.id] = storeRecordFactory(role)));

			firstLoad.value = true;
		} catch (e: any) {
			throw new ApiError('accounts-module.roles.fetch.failed', e, 'Fetching roles failed.');
		} finally {
			semaphore.value.fetching.items = false;
		}

		return true;
	};

	const add = async (payload: IRolesAddActionPayload): Promise<IRole> => {
		const newRole = storeRecordFactory({
			...{
				id: payload?.id,
				type: payload?.type,
				draft: payload?.draft,
			},
			...payload.data,
		});

		semaphore.value.creating.push(newRole.id);

		data.value[newRole.id] = newRole;

		if (newRole.draft) {
			semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newRole.id);

			return newRole;
		} else {
			try {
				const createdRole = await axios.post<IRoleResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/roles`,
					jsonApiFormatter.serialize({
						stuff: newRole,
					})
				);

				const createdRoleModel = jsonApiFormatter.deserialize(createdRole.data) as IRoleResponseModel;

				data.value[createdRoleModel.id] = storeRecordFactory({
					...createdRoleModel,
					...{ accountId: createdRoleModel.account.id },
				});

				return data.value[createdRoleModel.id];
			} catch (e: any) {
				// Entity could not be created on api, we have to remove it from database
				delete data.value[newRole.id];

				throw new ApiError('accounts-module.roles.create.failed', e, 'Create new role failed.');
			} finally {
				semaphore.value.creating = semaphore.value.creating.filter((item) => item !== newRole.id);
			}
		}
	};

	const edit = async (payload: IRolesEditActionPayload): Promise<IRole> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.roles.update.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.roles.update.failed');
		}

		semaphore.value.updating.push(payload.id);

		// Get record stored in database
		const existingRecord = data.value[payload.id];
		// Update with new values
		const updatedRecord = { ...existingRecord, ...payload.data } as IRole;

		data.value[payload.id] = updatedRecord;

		if (updatedRecord.draft) {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);

			return data.value[payload.id];
		} else {
			try {
				const updatedRole = await axios.patch<IRoleResponseJson>(
					`/${ModulePrefix.ACCOUNTS}/v1/roles/${updatedRecord.id}`,
					jsonApiFormatter.serialize({
						stuff: updatedRecord,
					})
				);

				const updatedRoleModel = jsonApiFormatter.deserialize(updatedRole.data) as IRoleResponseModel;

				data.value[updatedRoleModel.id] = storeRecordFactory(updatedRoleModel);

				return data.value[updatedRoleModel.id];
			} catch (e: any) {
				// Updating entity on api failed, we need to refresh entity
				await get({ id: payload.id });

				throw new ApiError('accounts-module.roles.update.failed', e, 'Edit role failed.');
			} finally {
				semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
			}
		}
	};

	const save = async (payload: IRolesSaveActionPayload): Promise<IRole> => {
		if (semaphore.value.updating.includes(payload.id)) {
			throw new Error('accounts-module.roles.save.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.roles.save.failed');
		}

		semaphore.value.updating.push(payload.id);

		const recordToSave = data.value[payload.id];

		try {
			const savedRole = await axios.post<IRoleResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/roles`,
				jsonApiFormatter.serialize({
					stuff: recordToSave,
				})
			);

			const savedRoleModel = jsonApiFormatter.deserialize(savedRole.data) as IRoleResponseModel;

			data.value[savedRoleModel.id] = storeRecordFactory(savedRoleModel);

			return data.value[savedRoleModel.id];
		} catch (e: any) {
			throw new ApiError('accounts-module.roles.save.failed', e, 'Save draft role failed.');
		} finally {
			semaphore.value.updating = semaphore.value.updating.filter((item) => item !== payload.id);
		}
	};

	const remove = async (payload: IRolesRemoveActionPayload): Promise<boolean> => {
		if (semaphore.value.deleting.includes(payload.id)) {
			throw new Error('accounts-module.roles.delete.inProgress');
		}

		if (!Object.keys(data.value).includes(payload.id)) {
			throw new Error('accounts-module.roles.delete.failed');
		}

		semaphore.value.deleting.push(payload.id);

		const recordToDelete = data.value[payload.id];

		delete data.value[payload.id];

		if (recordToDelete.draft) {
			semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
		} else {
			try {
				await axios.delete(`/${ModulePrefix.ACCOUNTS}/v1/roles/${recordToDelete.id}`);
			} catch (e: any) {
				// Deleting entity on api failed, we need to refresh entity
				await get({ id: payload.id });

				throw new ApiError('accounts-module.roles.delete.failed', e, 'Delete role failed.');
			} finally {
				semaphore.value.deleting = semaphore.value.deleting.filter((item) => item !== payload.id);
			}
		}

		return true;
	};

	const socketData = async (payload: IRolesSocketDataActionPayload): Promise<boolean> => {
		if (
			![
				RoutingKeys.ROLE_DOCUMENT_REPORTED,
				RoutingKeys.ROLE_DOCUMENT_CREATED,
				RoutingKeys.ROLE_DOCUMENT_UPDATED,
				RoutingKeys.ROLE_DOCUMENT_DELETED,
			].includes(payload.routingKey as RoutingKeys)
		) {
			return false;
		}

		const body: RoleDocument = JSON.parse(payload.data);

		const isValid = jsonSchemaValidator.compile<RoleDocument>(exchangeDocumentSchema);

		try {
			if (!isValid(body)) {
				return false;
			}
		} catch {
			return false;
		}

		if (
			!Object.keys(data.value).includes(body.id) &&
			(payload.routingKey === RoutingKeys.ROLE_DOCUMENT_UPDATED || payload.routingKey === RoutingKeys.ROLE_DOCUMENT_DELETED)
		) {
			throw new Error('accounts-module.roles.update.failed');
		}

		if (payload.routingKey === RoutingKeys.ROLE_DOCUMENT_DELETED) {
			delete data.value[body.id];
		} else {
			if (payload.routingKey === RoutingKeys.ROLE_DOCUMENT_UPDATED && semaphore.value.updating.includes(body.id)) {
				return true;
			}

			const recordData = storeRecordFactory({
				id: body.id,
				type: {
					source: body.source,
					entity: 'role',
				},
				name: body.name,
				description: body.description,
				anonymous: body.anonymous,
				authenticated: body.authenticated,
				administrator: body.administrator,
			});

			if (body.id in data.value) {
				data.value[body.id] = { ...data.value[body.id], ...recordData };
			} else {
				data.value[body.id] = recordData;
			}
		}

		return true;
	};

	return { semaphore, firstLoad, data, firstLoadFinished, getting, fetching, get, fetch, add, edit, save, remove, socketData };
});

export const registerRolesStore = (pinia: Pinia): Store<string, IRolesState, object, IRolesActions> => {
	return useRoles(pinia);
};
