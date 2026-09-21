import { App, computed } from 'vue';

import axios, { AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import defaultsDeep from 'lodash.defaultsdeep';
import get from 'lodash.get';
import 'virtual:uno.css';

import { IAccountDetails, IExtensionOptions, injectStoresManager, provideAccountManager, useBackend } from '@fastybird/miniserver-core';

import { accountStoreKey, accountsStoreKey, emailsStoreKey, identitiesStoreKey, metaKey, rolesStoreKey, sessionStoreKey } from './configuration';
import locales, { MessageSchema } from './locales';
import {
	registerAccountStore,
	registerAccountsStore,
	registerEmailsStore,
	registerIdentitiesStore,
	registerRolesStore,
	registerSessionStore,
} from './models';
import { IAccount } from './models/types';
import moduleRouter from './router';

const SESSION_API_URL = '/v1/session';

export default {
	install: (app: App, options: IExtensionOptions<{ 'en-US': MessageSchema }>): void => {
		moduleRouter(options.router, app);

		app.provide(metaKey, options.meta);

		for (const [locale, translations] of Object.entries(locales)) {
			const currentMessages = options.i18n.global.getLocaleMessage(locale);
			const mergedMessages = defaultsDeep(currentMessages, { accountsModule: translations });

			options.i18n.global.setLocaleMessage(locale, mergedMessages);
		}

		const storesManager = injectStoresManager(app);

		const accountStore = registerAccountStore(options.store);
		const accountsStore = registerAccountsStore(options.store);
		const emailsStore = registerEmailsStore(options.store);
		const identitiesStore = registerIdentitiesStore(options.store);
		const rolesStore = registerRolesStore(options.store);
		const sessionStore = registerSessionStore(options.store);

		app.provide(accountStoreKey, accountStore);
		storesManager.addStore(accountStoreKey, accountStore);
		app.provide(accountsStoreKey, accountsStore);
		storesManager.addStore(accountsStoreKey, accountsStore);
		app.provide(emailsStoreKey, emailsStore);
		storesManager.addStore(emailsStoreKey, emailsStore);
		app.provide(identitiesStoreKey, identitiesStore);
		storesManager.addStore(identitiesStoreKey, identitiesStore);
		app.provide(rolesStoreKey, rolesStore);
		storesManager.addStore(rolesStoreKey, rolesStore);
		app.provide(sessionStoreKey, sessionStore);
		storesManager.addStore(sessionStoreKey, sessionStore);

		let refreshAccessTokenCall: Promise<any> | null = null;

		const { pendingRequests } = useBackend();

		// Set basic headers
		axios.interceptors.request.use((request): InternalAxiosRequestConfig => {
			const accessToken = sessionStore.data.accessToken;

			if (get(request, 'url', '').includes(SESSION_API_URL) && request.method === 'patch') {
				delete request.headers?.Authorization;
			} else if (accessToken !== null) {
				if (typeof request.headers !== 'undefined') {
					Object.assign(request.headers, { Authorization: `Bearer ${accessToken}` });
				} else {
					Object.assign(request, { headers: { Authorization: `Bearer ${accessToken}` } });
				}
			}

			return request;
		});

		// Add a response interceptor
		axios.interceptors.response.use(
			(response: AxiosResponse): AxiosResponse => {
				pendingRequests.value = Math.max(0, pendingRequests.value - 1);

				return response;
			},
			(error): Promise<any> => {
				const originalRequest = error.config;

				// Concurrent request check only for client side
				pendingRequests.value = Math.max(0, pendingRequests.value - 1);

				if (
					parseInt(get(error, 'response.status', 200), 10) === 401 &&
					!originalRequest.url.includes(SESSION_API_URL) &&
					!get(originalRequest, '_retry', false)
				) {
					if (originalRequest.url.includes(SESSION_API_URL) && originalRequest.method !== 'patch') {
						return Promise.reject(error);
					}

					// if the error is 401 and has sent already been retried
					originalRequest._retry = true; // now it can be retried

					if (refreshAccessTokenCall === null) {
						refreshAccessTokenCall = sessionStore
							.refresh()
							.then((): Promise<any> => {
								// Reset call instance
								refreshAccessTokenCall = null;

								originalRequest.headers.Authorization = `Bearer ${sessionStore.data.accessToken}`;

								return axios(originalRequest); // retry the request that errored out
							})
							.catch((): Promise<any> => {
								// Reset call instance
								refreshAccessTokenCall = null;

								return Promise.reject(error);
							});
					}

					if (refreshAccessTokenCall === null) {
						return Promise.reject('accountsModule.Refresh token failed');
					}

					return refreshAccessTokenCall;
				} else if (
					parseInt(get(error, 'response.status', 200), 10) >= 500 &&
					parseInt(get(error, 'response.status', 200), 10) < 600 &&
					!get(originalRequest, '_retry', false)
				) {
					// if the error is 5xx and has sent already been retried
					originalRequest._retry = true; // now it can be retried

					return axios(originalRequest); // retry the request that errored out
				} else {
					return Promise.reject(error);
				}
			}
		);

		provideAccountManager(app, {
			isSignedIn: computed<boolean>((): boolean => {
				return sessionStore.isSignedIn();
			}),
			isLocked: computed<boolean>((): boolean => {
				return false;
			}),
			details: computed<IAccountDetails | null>((): IAccountDetails | null => {
				const account: IAccount | null = sessionStore.account();

				if (account === null) {
					return null;
				}

				return {
					name: account.name,
					email: account.email?.address ?? `${account.name}@unknown.com`,
				};
			}),
			signIn: async (credentials): Promise<boolean> => {
				try {
					await sessionStore.create({
						uid: credentials.username,
						password: credentials.password,
					});

					return true;
				} catch {
					return false;
				}
			},
			signOut: async (): Promise<boolean> => {
				try {
					await sessionStore.clear();

					return true;
				} catch {
					return false;
				}
			},
			lock: (): Promise<boolean> => {
				return Promise.resolve(true);
			},
			canAccess: (): Promise<boolean> => {
				return Promise.resolve(true);
			},
		});
	},
};

export * from './configuration';
export * from './components';
export * from './composables';

export * from './types';
