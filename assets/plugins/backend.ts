import { App, InjectionKey } from 'vue';
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import get from 'lodash/get';

import { ModulePrefix } from '@fastybird/metadata-library';

import { useSession } from '@fastybird/accounts-module';

const SESSION_API_URL = '/v1/session';

interface IAxiosOptions {
	apiPrefix: string;
	apiTarget: string | null;
	apiKey: string | null;
	apiPrefixedModules: boolean;
}

export const key: InjectionKey<AxiosInstance> = Symbol('backend');

export default {
	install: (app: App, options: IAxiosOptions): void => {
		const session = useSession();

		const baseUrl = options.apiTarget !== null ? options.apiTarget : options.apiPrefix;

		axios.defaults.baseURL = baseUrl;

		let refreshAccessTokenCall: Promise<any> | null = null;

		let pendingRequests = 0;

		const MAX_REQUESTS_COUNT = 10;
		const MAX_REQUESTS_COUNT_DELAY = 10;

		axios.interceptors.request.use((request: AxiosRequestConfig): Promise<any> => {
			request.baseURL = baseUrl;

			if (typeof request.headers !== 'undefined') {
				Object.assign(request.headers, { 'Content-Type': 'application/vnd.api+json' });
			}

			if (options.apiKey !== null) {
				if (typeof request.headers !== 'undefined') {
					Object.assign(request.headers, { 'X-Api-Key': options.apiKey });
				}
			}

			return new Promise((resolve) => {
				const interval = setInterval(() => {
					if (pendingRequests < MAX_REQUESTS_COUNT) {
						pendingRequests++;

						clearInterval(interval);

						resolve(request);
					}
				}, MAX_REQUESTS_COUNT_DELAY);
			});
		});

		// Set basic headers
		axios.interceptors.request.use((request: AxiosRequestConfig): AxiosRequestConfig => {
			const accessToken = session.data.accessToken;

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

		// Modify api url prefix
		axios.interceptors.request.use((request: AxiosRequestConfig): AxiosRequestConfig => {
			if (!options.apiPrefixedModules && typeof request.url !== 'undefined') {
				Object.values(ModulePrefix).forEach((modulePrefix) => {
					request.url = request.url?.replace(new RegExp(`^/${modulePrefix}`, 'g'), ''); // Remove base path
				});
			}

			return request;
		});

		// Add a response interceptor
		axios.interceptors.response.use(
			(response: AxiosResponse): AxiosResponse => {
				pendingRequests = Math.max(0, pendingRequests - 1);

				return response;
			},
			(error): Promise<any> => {
				const originalRequest = error.config;

				// Concurrent request check only for client side
				pendingRequests = Math.max(0, pendingRequests - 1);

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
						refreshAccessTokenCall = session
							.refresh()
							.then((): Promise<any> => {
								// Reset call instance
								refreshAccessTokenCall = null;

								originalRequest.headers.Authorization = `Bearer ${session.data.accessToken}`;

								return axios(originalRequest); // retry the request that errored out
							})
							.catch((): Promise<any> => {
								// Reset call instance
								refreshAccessTokenCall = null;

								return Promise.reject(error);
							});
					}

					if (refreshAccessTokenCall === null) {
						return Promise.reject('Refresh token failed');
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

		app.provide(key, axios);
	},
};

export function useAxios(): AxiosInstance {
	return axios;
}
