import { ref } from 'vue';
import { useCookies } from 'vue3-cookies';

import { Pinia, Store, defineStore } from 'pinia';

import axios from 'axios';
import { Jsona } from 'jsona';
import { jwtDecode } from 'jwt-decode';
import lodashGet from 'lodash.get';

import { ModulePrefix, injectStoresManager } from '@fastybird/miniserver-core';

import { accountsStoreKey } from '../../configuration';
import { ApiError } from '../../errors';
import { JsonApiJsonPropertiesMapper, JsonApiModelPropertiesMapper } from '../../jsonapi';
import { CookiesPlugin } from '../../types';
import { IAccount } from '../accounts/types';

import {
	ISession,
	ISessionActions,
	ISessionCreateActionPayload,
	ISessionRecordFactoryPayload,
	ISessionResponseJson,
	ISessionResponseModel,
	ISessionState,
	ISessionStateSemaphore,
	SessionStoreSetup,
} from './types';

export const ACCESS_TOKEN_COOKIE_NAME = 'token';
export const REFRESH_TOKEN_COOKIE_NAME = 'refresh_token';

const jsonApiFormatter = new Jsona({
	modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
	jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
});

const defaultSemaphore = {
	fetching: false,
	creating: false,
	updating: false,
};

const defaultData = {
	accessToken: null,
	refreshToken: null,
	tokenType: 'Bearer',
	tokenExpiration: null,
	accountId: null,
};

const storeRecordFactory = (cookies: CookiesPlugin, rawData: ISessionRecordFactoryPayload): ISession => {
	const decodedAccessToken = jwtDecode<{ exp: number; user: string }>(rawData.accessToken);
	const decodedRefreshToken = jwtDecode<{ exp: number; user: string }>(rawData.refreshToken);

	const state = {
		accessToken: rawData.accessToken,
		refreshToken: rawData.refreshToken,
		tokenExpiration: new Date(decodedAccessToken.exp * 1000).toISOString(),
		tokenType: rawData.tokenType,

		accountId: lodashGet(decodedAccessToken, 'user'),
	};

	cookies.set(
		ACCESS_TOKEN_COOKIE_NAME,
		rawData.accessToken,
		new Date(decodedAccessToken.exp * 1000).getTime() / 1000 - new Date().getTime() / 1000,
		'/'
	);

	cookies.set(
		REFRESH_TOKEN_COOKIE_NAME,
		rawData.refreshToken,
		new Date(decodedRefreshToken.exp * 1000).getTime() / 1000 - new Date().getTime() / 1000,
		'/'
	);

	return state;
};

const readCookie = (cookies: CookiesPlugin, name: string): string | null => {
	if (cookies.get(name) !== null && typeof cookies.get(name) !== 'undefined' && cookies.get(name) !== '') {
		return cookies.get(name);
	}

	return null;
};

export const useSession = defineStore<'accounts_module_session', SessionStoreSetup>('accounts_module_session', (): SessionStoreSetup => {
	const storesManager = injectStoresManager();

	const { cookies } = useCookies();

	const semaphore = ref<ISessionStateSemaphore>(defaultSemaphore);

	const data = ref<ISession>(defaultData);

	const accessToken = (): string | null => data.value.accessToken;

	const refreshToken = (): string | null => data.value.refreshToken;

	const accountId = (): IAccount['id'] | null => data.value.accountId;

	const account = (): IAccount | null => {
		if (data.value.accountId === null) {
			return null;
		}

		const accountsStore = storesManager.getStore(accountsStoreKey);

		return accountsStore.findById(data.value.accountId);
	};

	const isSignedIn = (): boolean => data.value.accountId !== null;

	const initialize = (): void => {
		if (data.value.accessToken !== null) return;

		const accessTokenCookie = readCookie(cookies, ACCESS_TOKEN_COOKIE_NAME);
		const refreshTokenCookie = readCookie(cookies, REFRESH_TOKEN_COOKIE_NAME);

		data.value.accessToken = null;
		data.value.refreshToken = null;
		data.value.tokenExpiration = null;
		data.value.accountId = null;

		if (refreshTokenCookie !== null) {
			data.value.refreshToken = refreshTokenCookie;
		} else {
			return;
		}

		if (accessTokenCookie !== null) {
			const decodedAccessToken = jwtDecode<{ exp: number; user: string }>(accessTokenCookie);

			data.value.accessToken = accessTokenCookie;
			data.value.tokenExpiration = new Date(decodedAccessToken.exp * 1000).toISOString();
			data.value.accountId = decodedAccessToken.user;
		}
	};

	const clear = (): void => {
		semaphore.value = defaultSemaphore;
		data.value = defaultData;

		cookies.remove(ACCESS_TOKEN_COOKIE_NAME);
		cookies.remove(REFRESH_TOKEN_COOKIE_NAME);
	};

	const fetch = async (): Promise<boolean> => {
		if (semaphore.value.fetching) {
			return false;
		}

		const accountsStore = storesManager.getStore(accountsStoreKey);

		semaphore.value.fetching = true;

		try {
			const response = await axios.get<ISessionResponseJson>(`/${ModulePrefix.ACCOUNTS}/v1/session`);

			const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

			data.value = storeRecordFactory(cookies, {
				accessToken: responseModel.token,
				refreshToken: responseModel.refresh,
				tokenType: responseModel.tokenType,
			});

			await accountsStore.get({ id: responseModel.account.id });

			return true;
		} catch (e: any) {
			throw new ApiError('session.fetch.failed', e, 'Fetching session failed.');
		} finally {
			semaphore.value.fetching = false;
		}
	};

	const create = async (payload: ISessionCreateActionPayload): Promise<boolean> => {
		if (semaphore.value.creating) {
			return false;
		}

		const accountsStore = storesManager.getStore(accountsStoreKey);

		const dataFormatter = new Jsona();

		semaphore.value.creating = true;

		try {
			const response = await axios.post<ISessionResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/session`,
				dataFormatter.serialize({
					stuff: {
						type: 'com.fastybird.accounts-module/session',

						uid: payload.uid,
						password: payload.password,
					},
				})
			);

			const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

			data.value = storeRecordFactory(cookies, {
				accessToken: responseModel.token,
				refreshToken: responseModel.refresh,
				tokenType: responseModel.tokenType,
			});

			await accountsStore.get({ id: responseModel.account.id });

			return true;
		} catch (e: any) {
			throw new ApiError('session.create.failed', e, 'Create session failed.');
		} finally {
			semaphore.value.creating = false;
		}
	};

	const refresh = async (): Promise<boolean> => {
		if (semaphore.value.updating) {
			return false;
		}

		cookies.remove(ACCESS_TOKEN_COOKIE_NAME);

		const refreshToken = readCookie(cookies, REFRESH_TOKEN_COOKIE_NAME);

		if (refreshToken === null) {
			return false;
		}

		const dataFormatter = new Jsona();

		semaphore.value.updating = true;

		try {
			const response = await axios.patch<ISessionResponseJson>(
				`/${ModulePrefix.ACCOUNTS}/v1/session`,
				dataFormatter.serialize({
					stuff: {
						type: 'com.fastybird.accounts-module/session',

						refresh: refreshToken,
					},
				})
			);

			const responseModel = jsonApiFormatter.deserialize(response.data) as ISessionResponseModel;

			data.value = storeRecordFactory(cookies, {
				accessToken: responseModel.token,
				refreshToken: responseModel.refresh,
				tokenType: responseModel.tokenType,
			});

			return true;
		} catch (e: any) {
			throw new ApiError('session.refresh.failed', e, 'Refresh session failed.');
		} finally {
			semaphore.value.updating = false;
		}
	};

	return { semaphore, data, accessToken, refreshToken, accountId, account, isSignedIn, initialize, clear, fetch, create, refresh };
});

export const registerSessionStore = (pinia: Pinia): Store<string, ISessionState, object, ISessionActions> => {
	return useSession(pinia);
};
